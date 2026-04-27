<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Models\Flat;
use App\Models\Maintenance;
use App\Models\Society;
use App\Services\MaintenanceGenerationService;
use App\Services\MaintenancePaymentService;
use App\Services\MaintenanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceController extends Controller
{
    protected MaintenanceService $maintenanceService;
    protected MaintenanceGenerationService $generationService;
    protected MaintenancePaymentService $paymentService;

    public function __construct(
        MaintenanceService $maintenanceService,
        MaintenanceGenerationService $generationService,
        MaintenancePaymentService $paymentService
    ) {
        $this->maintenanceService = $maintenanceService;
        $this->generationService = $generationService;
        $this->paymentService = $paymentService;

        $this->middleware('permission:maintenance.view')->only(['index', 'show']);
        $this->middleware('permission:maintenance.create')->only(['create', 'generate']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Maintenance::with(['flat.tower', 'creator']);

        if ($user->isResident()) {
            $query->whereIn('flat_id', $user->activeFlats()->pluck('id'));
        } elseif ($user->hasRole('society-admin') && $user->society_id) {
            $query->forSociety($user->society_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $query->forMonth($request->month);
        }

        $summary = $this->maintenanceService->getSummaryFromQuery(clone $query);
        $maintenances = $query->orderByDesc('month')->paginate(50);

        return view('maintenance.index', compact('maintenances', 'summary'));
    }

    public function create()
    {
        $user = Auth::user();

        $flats = Flat::with('tower')
            ->where('is_active', true)
            ->when($user->hasRole('society-admin'), function ($q) use ($user) {
                $q->whereHas('tower', function ($t) use ($user) {
                    $t->where('society_id', $user->society_id);
                });
            })
            ->get();

        $societies = $user->isSuperAdmin()
            ? Society::active()->orderBy('name')->get()
            : collect();

        return view('maintenance.create', compact('flats', 'societies'));
    }

    public function generate(StoreMaintenanceRequest $request)
    {
        try {
            $user = Auth::user();
            $societyId = $user->isSuperAdmin()
                ? (int) $request->validated('society_id')
                : $user->society_id;

            if (!$societyId) {
                abort(400, 'Society not resolved.');
            }

            $count = $this->generationService->generateForSociety(
                societyId: $societyId,
                month: Carbon::createFromFormat('Y-m', $request->month),
                generatedBy: $user->id
            );

            return redirect()
                ->route('maintenance.index')
                ->with('success', "Maintenance generated for {$count} flat(s).");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Maintenance $maintenance)
    {
        if (!$this->canViewMaintenance($maintenance)) {
            abort(403);
        }

        $maintenance->load([
            'flat.tower.society',
            'creator',
            'updater',
            'payments.creator',
        ]);

        return view('maintenance.show', compact('maintenance'));
    }

    public function paymentForm(Maintenance $maintenance)
    {
        if (!$this->canManagePayment($maintenance)) {
            abort(403);
        }

        $maintenance->load('flat.tower.society');

        return view('maintenance.payment', compact('maintenance'));
    }

    public function processPayment(
        ProcessPaymentRequest $request,
        Maintenance $maintenance
    ) {
        if (!$this->canManagePayment($maintenance)) {
            abort(403);
        }

        try {
            $this->paymentService->recordPayment(
                maintenance: $maintenance,
                amount: (float) $request->validated('amount'),
                performedBy: Auth::id(),
                paymentMode: $request->validated('payment_mode'),
                transactionId: $request->validated('transaction_id'),
                remarks: $request->validated('remarks')
            );

            return back()->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function canViewMaintenance(Maintenance $maintenance): bool
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasRole('society-admin')) {
            return $maintenance->flat
                && $maintenance->flat->tower
                && $maintenance->flat->tower->society_id === $user->society_id;
        }

        if ($user->isResident()) {
            return $user->activeFlats()
                ->where('flats.id', $maintenance->flat_id) // ✅ FIXED
                ->exists();
        }

        return $user->hasPermission('maintenance.view');
    }

    private function canManagePayment(Maintenance $maintenance): bool
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasRole('society-admin')) {
            return $maintenance->flat
                && $maintenance->flat->tower
                && $maintenance->flat->tower->society_id === $user->society_id;
        }

        if ($user->isResident()) {
            return $user->activeFlats()
                ->where('flats.id', $maintenance->flat_id) // ✅ FIXED
                ->exists();
        }

        return $user->hasPermission('maintenance.update');
    }
}