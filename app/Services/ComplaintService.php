<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Complaint;
use App\Models\ComplaintComment;
use Illuminate\Support\Facades\DB;

/**
 * ComplaintService
 * 
 * Manages complaint lifecycle and assignments
 * Handles status transitions, comments, and notifications
 * 
 * Purpose: Streamline complaint management workflow
 * Input: Complaint data, assignments, comments
 * Output: Complaint models, status updates
 * Side Effects: Creates complaints, logs activities, sends notifications
 */
class ComplaintService
{
    protected ActivityLogService $activityLog;
    protected FileService $fileService;
    protected NotificationService $notificationService;

    public function __construct(
        ActivityLogService $activityLog,
        FileService $fileService,
        NotificationService $notificationService
    )
    {
        $this->activityLog = $activityLog;
        $this->fileService = $fileService;
        $this->notificationService = $notificationService;
    }

    /**
     * Create new complaint
     * 
     * @param array $data
     * @param array|null $files
     * @return Complaint
     */
    public function create(array $data, ?array $files = null): Complaint
    {
        DB::beginTransaction();
        try {
            // Ensure user_id and created_by are set
            if (!isset($data['user_id'])) {
                $data['user_id'] = auth()->id();
            }

            $data['created_by'] = auth()->id();

            // Create complaint
            $complaint = Complaint::create($data);

            // Upload files if provided
            if ($files && is_array($files)) {
                $this->fileService->uploadMultiple($files, 'complaints', $complaint->id);
            }

            $this->activityLog->logCreate('complaint', $complaint->id, $complaint->toArray());
            $this->notifyComplaintRaised($complaint);

            DB::commit();
            return $complaint->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('ComplaintService::create failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Assign complaint to staff
     * Auto-updates status to 'in_progress'
     * 
     * @param int $complaintId
     * @param int $staffId
     * @return bool
     */
    public function assign(int $complaintId, int $staffId): bool
    {
        $complaint = Complaint::findOrFail($complaintId);
        $oldData = $complaint->toArray();

        if (in_array($complaint->status, [ComplaintStatus::RESOLVED, ComplaintStatus::CLOSED], true)) {
            throw new \InvalidArgumentException('Cannot assign resolved or closed complaints.');
        }

        DB::beginTransaction();
        try {
            // Update complaint with assignment and status
            $result = $complaint->update([
                'assigned_to' => $staffId,
                'assigned_at' => now(),
                'status' => 'in_progress',  // Auto-update status
                'updated_by' => auth()->id(),
            ]);

            if ($result) {
                $this->activityLog->logUpdate(
                    'complaint',
                    $complaint->id,
                    $oldData,
                    $complaint->fresh()->toArray()
                );

                // Add system comment
                $this->addComment(
                    $complaint->id,
                    "Complaint assigned to " . User::find($staffId)->name . " and status changed to In Progress.",
                    true
                );

                $this->notifyComplaintAssigned($complaint->fresh());
            }

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Resolve complaint
     * Auto-updates status to 'resolved'
     * 
     * @param int $complaintId
     * @param string $resolutionNote
     * @return bool
     */
    public function resolve(int $complaintId, string $resolutionNote): bool
    {
        $complaint = Complaint::findOrFail($complaintId);
        $oldData = $complaint->toArray();

        DB::beginTransaction();
        try {
            $result = $complaint->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolution_note' => $resolutionNote,
                'updated_by' => auth()->id(),
            ]);

            if ($result) {
                $this->activityLog->logUpdate(
                    'complaint',
                    $complaint->id,
                    $oldData,
                    $complaint->fresh()->toArray()
                );

                // Add system comment
                $this->addComment(
                    $complaint->id,
                    "Complaint marked as resolved.",
                    false  // Public comment
                );

                $this->notifyComplaintUpdated(
                    $complaint->fresh(),
                    'Complaint resolved',
                    'Your complaint has been marked as resolved.'
                );
            }

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add comment to complaint
     * 
     * @param int $complaintId
     * @param string $comment
     * @param bool $isInternal
     * @return ComplaintComment
     */
    public function addComment(int $complaintId, string $comment, bool $isInternal = false): ComplaintComment
    {
        return ComplaintComment::create([
            'complaint_id' => $complaintId,
            'user_id' => Auth::id(),
            'comment' => $comment,
            'is_internal' => $isInternal,
        ]);
    }

    /**
     * Update complaint status
     * 
     * @param int $complaintId
     * @param string $status
     * @return bool
     */
    public function updateStatus(int $complaintId, string $status): bool
    {
        $complaint = Complaint::findOrFail($complaintId);
        $oldData = $complaint->toArray();

        $result = $complaint->update([
            'status' => $status,
            'updated_by' => Auth::id(),
        ]);

        if ($result) {
            $complaint = $complaint->fresh();
            $this->activityLog->logUpdate(
                'complaint',
                $complaint->id,
                $oldData,
                $complaint->toArray()
            );

            $this->notifyComplaintUpdated(
                $complaint,
                'Complaint status updated',
                'Your complaint status is now ' . ucfirst(str_replace('_', ' ', (string) $status)) . '.'
            );
        }

        return $result;
    }

    /**
     * Reopen a resolved/closed complaint under dispute.
     */
    public function dispute(int $complaintId, string $reason, ?array $files = null): bool
    {
        $complaint = Complaint::findOrFail($complaintId);
        $oldData = $complaint->toArray();

        if ($complaint->status !== ComplaintStatus::RESOLVED) {
            throw new \InvalidArgumentException('Only resolved complaints can be disputed.');
        }

        return DB::transaction(function () use ($complaint, $oldData, $reason, $files) {
            $result = $complaint->update([
                'status' => ComplaintStatus::DISPUTED,
                'updated_by' => Auth::id(),
            ]);

            if ($files && is_array($files)) {
                $this->fileService->uploadMultiple($files, 'complaints', $complaint->id);
            }

            $this->addComment(
                $complaint->id,
                'Resident disputed the resolution: ' . $reason,
                false
            );

            $complaint = $complaint->fresh();

            $this->activityLog->logUpdate(
                'complaint',
                $complaint->id,
                $oldData,
                $complaint->toArray()
            );

            $this->notifyComplaintDisputed($complaint, $reason);

            return $result;
        });
    }

    /**
     * Check for duplicate complaints
     * 
     * @param int $userId
     * @param string $subject
     * @param int $withinDays
     * @return bool
     */
    public function checkDuplicate(int $userId, string $subject, int $withinDays = 7): bool
    {
        return Complaint::where('user_id', $userId)
            ->where('subject', 'LIKE', "%$subject%")
            ->where('created_at', '>=', now()->subDays($withinDays))
            ->exists();
    }

    /**
     * Get complaints dashboard summary
     * 
     * @return array
     */
    public function getDashboardSummary(?int $societyId = null): array
    {
        $query = Complaint::query();

        if ($societyId) {
            $query->whereHas('flat.tower', function ($towerQuery) use ($societyId) {
                $towerQuery->where('society_id', $societyId);
            });
        }

        return [
            'open' => (clone $query)->where('status', 'open')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
            'high_priority' => (clone $query)->whereIn('priority', ['high', 'urgent'])
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
        ];
    }

    private function notifyComplaintRaised(Complaint $complaint): void
    {
        $complaint->loadMissing('flat.tower');

        if ($complaint->flat?->tower?->society_id) {
            $this->notificationService->sendToRole(
                'society-admin',
                NotificationType::COMPLAINT_RAISED,
                'New complaint raised',
                "A new complaint has been submitted: {$complaint->subject}",
                'complaints',
                $complaint->id,
                route('complaints.show', $complaint),
                $complaint->flat->tower->society_id
            );
        }
    }

    private function notifyComplaintAssigned(Complaint $complaint): void
    {
        $complaint->loadMissing('assignedStaff');

        if ($complaint->assignedStaff) {
            $this->notificationService->sendToUser(
                $complaint->assignedStaff,
                NotificationType::COMPLAINT_UPDATED,
                'Complaint assigned',
                "You have been assigned complaint {$complaint->ticket_number}.",
                'complaints',
                $complaint->id,
                route('complaints.show', $complaint)
            );
        }
    }

    private function notifyComplaintUpdated(Complaint $complaint, string $title, string $message): void
    {
        $complaint->loadMissing('user');

        if ($complaint->user) {
            $this->notificationService->sendToUser(
                $complaint->user,
                NotificationType::COMPLAINT_UPDATED,
                $title,
                $message,
                'complaints',
                $complaint->id,
                route('complaints.show', $complaint)
            );
        }
    }

    private function notifyComplaintDisputed(Complaint $complaint, string $reason): void
    {
        $complaint->loadMissing(['assignedStaff', 'flat.tower', 'user']);

        if ($complaint->assignedStaff) {
            $this->notificationService->sendToUser(
                $complaint->assignedStaff,
                NotificationType::COMPLAINT_UPDATED,
                'Complaint disputed by resident',
                "Complaint {$complaint->ticket_number} has been disputed: {$reason}",
                'complaints',
                $complaint->id,
                route('complaints.show', $complaint)
            );
        }

        if ($complaint->flat?->tower?->society_id) {
            $this->notificationService->sendToRole(
                'society-admin',
                NotificationType::COMPLAINT_UPDATED,
                'Complaint reopened under dispute',
                "Complaint {$complaint->ticket_number} has been disputed by the resident.",
                'complaints',
                $complaint->id,
                route('complaints.show', $complaint),
                $complaint->flat->tower->society_id
            );
        }

        if ($complaint->user) {
            $this->notificationService->sendToUser(
                $complaint->user,
                NotificationType::COMPLAINT_UPDATED,
                'Complaint reopened',
                'Your complaint has been reopened and marked as disputed.',
                'complaints',
                $complaint->id,
                route('complaints.show', $complaint)
            );
        }
    }
}
