<?php

namespace App\Http\Controllers;

use App\Enums\BillingCycle;
use App\Enums\CalculationType;
use App\Enums\LateFeeType;
use App\Models\MaintenancePolicy;
use App\Models\MaintenancePolicyTemplate;
use App\Models\Society;
use App\Services\MaintenanceGenerationService;
use App\Services\MaintenancePolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

class MaintenancePolicyController extends Controller
{
    protected MaintenancePolicyService $service;
    protected MaintenanceGenerationService $generationService;

    public function __construct(
        MaintenancePolicyService $service,
        MaintenanceGenerationService $generationService
    ) {
        $this->service = $service;
        $this->generationService = $generationService;

        $this->middleware(['auth', 'verified']);
    }

    public function index()
    {
        if (!$this->canViewPolicies()) {
            abort(403);
        }

        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            $policies = MaintenancePolicy::with('template', 'society')
                ->latest()
                ->get();
        } else {
            if (!$user->society_id) {
                abort(403, 'User not assigned to any society.');
            }

            $policies = MaintenancePolicy::with('template')
                ->forSociety($user->society_id)
                ->latest()
                ->get();
        }

        return view('maintenance.policies.index', compact('policies'));
    }

    public function create()
    {
        if (!$this->canManagePolicies()) {
            abort(403);
        }

        $societies = Auth::user()->isSuperAdmin()
            ? Society::active()->orderBy('name')->get()
            : collect();

        return view('maintenance.policies.create', compact('societies'));
    }

    public function store(Request $request)
    {
        if (!$this->canManagePolicies()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'society_id' => 'nullable|exists:societies,id',
            'billing_cycle' => ['required', new Enum(BillingCycle::class)],
            'calculation_type' => ['required', new Enum(CalculationType::class)],
            'base_amount' => 'nullable|numeric|min:0',
            'late_fee_type' => ['nullable', new Enum(LateFeeType::class)],
            'late_fee_value' => 'nullable|numeric|min:0',
            'grace_days' => 'required|integer|min:0',
            'allow_partial_payment' => 'required|boolean',
            'effective_from' => 'required|date|after_or_equal:today',
            'type_amounts' => 'nullable|array',
        ]);

        if (($validated['calculation_type'] instanceof CalculationType
                ? $validated['calculation_type']
                : CalculationType::from($validated['calculation_type'])
            ) === CalculationType::FLAT_TYPE) {
            $requiredTypes = ['1BHK', '2BHK', '3BHK', '4BHK', 'Penthouse'];

            foreach ($requiredTypes as $type) {
                if (
                    !isset($validated['type_amounts'][$type]) ||
                    !is_numeric($validated['type_amounts'][$type]) ||
                    $validated['type_amounts'][$type] <= 0
                ) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            "type_amounts.{$type}" => "Amount required for {$type}",
                        ]);
                }
            }
        }

        $user = Auth::user();

        if (!$user->society_id && !$user->isSuperAdmin()) {
            abort(403, 'User not assigned to any society.');
        }

        $societyId = $user->isSuperAdmin()
            ? $request->input('society_id')
            : $user->society_id;

        if (!$societyId) {
            abort(400, 'Society not resolved.');
        }

        $template = MaintenancePolicyTemplate::create([
            'name' => $validated['name'],
            'billing_cycle' => $validated['billing_cycle'],
            'calculation_type' => $validated['calculation_type'],
            'base_amount' => $validated['base_amount'] ?? null,
            'type_amounts' => $validated['type_amounts'] ?? null,
            'late_fee_type' => $validated['late_fee_type'] ?? null,
            'late_fee_value' => $validated['late_fee_value'] ?? null,
            'grace_days' => $validated['grace_days'],
            'allow_partial_payment' => $validated['allow_partial_payment'],
            'created_by' => $user->id,
            'is_active' => true,
        ]);

        $this->service->createAndActivatePolicy(
            societyId: (int) $societyId,
            data: [
                'template_id' => $template->id,
                'effective_from' => $validated['effective_from'],
            ],
            performedBy: $user->id
        );

        return redirect()
            ->route('maintenance.policies.index')
            ->with('success', 'New maintenance policy activated.');
    }

    public function activate(MaintenancePolicy $policy)
    {
        if (!$this->canManagePolicies()) {
            abort(403);
        }

        $user = Auth::user();

        if (!$user->isSuperAdmin() && $policy->society_id !== $user->society_id) {
            abort(403);
        }

        $this->service->activatePolicy(
            societyId: $policy->society_id,
            templateId: $policy->template_id,
            effectiveFrom: $policy->effective_from ?? now(),
            performedBy: $user->id
        );

        return back()->with('success', 'Policy activated successfully.');
    }

    public function manualGenerate(Request $request)
    {
        if (!$this->canManagePolicies()) {
            abort(403);
        }

        $user = Auth::user();
        $month = now()->startOfMonth();

        if ($user->isSuperAdmin()) {
            $societyId = $request->input('society_id');

            if (!$societyId) {
                abort(400, 'Super Admin must specify society.');
            }
        } else {
            if (!$user->society_id) {
                abort(403, 'User not assigned to any society.');
            }

            $societyId = $user->society_id;
        }

        $this->generationService->generateForSociety(
            societyId: (int) $societyId,
            month: $month,
            generatedBy: $user->id
        );

        return back()->with('success', 'Maintenance generated successfully.');
    }

    private function canViewPolicies(): bool
    {
        $user = Auth::user();

        return $user->isSuperAdmin()
            || $user->isSocietyAdmin()
            || $user->hasPermission('maintenance.create')
            || $user->hasPermission('maintenance.policy.view');
    }

    private function canManagePolicies(): bool
    {
        $user = Auth::user();

        return $user->isSuperAdmin()
            || $user->isSocietyAdmin()
            || $user->hasPermission('maintenance.create')
            || $user->hasPermission('maintenance.policy.create');
    }
}
