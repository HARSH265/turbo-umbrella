<?php

namespace App\Services;

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

    public function __construct(ActivityLogService $activityLog, FileService $fileService)
    {
        $this->activityLog = $activityLog;
        $this->fileService = $fileService;
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
            $this->activityLog->logUpdate(
                'complaint',
                $complaint->id,
                $oldData,
                $complaint->fresh()->toArray()
            );
        }

        return $result;
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
    public function getDashboardSummary(): array
    {
        return [
            'open' => Complaint::where('status', 'open')->count(),
            'in_progress' => Complaint::where('status', 'in_progress')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'high_priority' => Complaint::whereIn('priority', ['high', 'urgent'])
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
        ];
    }
}
