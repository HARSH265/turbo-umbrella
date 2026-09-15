<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\AmenityBooking;
use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\Flat;
use App\Models\Maintenance;
use App\Models\MaintenancePayment;
use App\Models\MaintenancePolicy;
use App\Models\MaintenancePolicyTemplate;
use App\Models\Notice;
use App\Models\Society;
use App\Models\Tower;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DemoDataSeeder
 *
 * Fills every operational module with realistic demo data so the UI is not
 * empty on a fresh install: towers, flats, residents, complaints + comments,
 * maintenance policy + bills + payments, notices, visitors, vehicles,
 * amenities and bookings.
 *
 * Safe to re-run: every insert is keyed with firstOrCreate / updateOrCreate.
 * Depends on RoleSeeder + TestUsersSeeder having run first.
 *
 * Run on its own with:
 *   php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    private int $adminId;

    public function run(): void
    {
        $society = Society::where('code', 'TEST001')->first();

        if (!$society) {
            $this->command->error('Society TEST001 not found. Run TestUsersSeeder first (php artisan db:seed).');
            return;
        }

        $admin = User::where('email', 'admin@ssms.local')->first();
        $societyAdmin = User::where('email', 'societyadmin@ssms.local')->first();
        $this->adminId = $admin?->id ?? 1;

        $this->command->info('Seeding demo data for ' . $society->name . ' ...');

        $towers = $this->seedTowers($society);
        $flats = $this->seedFlats($towers);
        $residents = User::withRole('resident')->where('society_id', $society->id)->orderBy('id')->get();
        $staff = User::withRole('staff')->where('society_id', $society->id)->orderBy('id')->get();

        $this->attachResidentsToFlats($residents, $flats);
        $this->seedVehicles($society, $flats, $residents);
        $this->seedAmenities($society, $flats, $residents);
        $this->seedNotices($society, $societyAdmin ?? $admin);
        $this->seedComplaints($residents, $flats, $staff, $societyAdmin ?? $admin);
        $this->seedMaintenance($society, $flats);
        $this->seedVisitors($flats, $staff, $societyAdmin ?? $admin);

        $this->report($society);
    }

    /* ------------------------------------------------------------------ */
    /* Structure                                                           */
    /* ------------------------------------------------------------------ */

    private function seedTowers(Society $society): array
    {
        $towers = [];

        foreach ([['Tower A', 10], ['Tower B', 12], ['Tower C', 8]] as [$name, $floors]) {
            $towers[$name] = Tower::firstOrCreate(
                ['society_id' => $society->id, 'name' => $name],
                [
                    'total_floors' => $floors,
                    'is_active' => true,
                    'created_by' => $this->adminId,
                ]
            );
        }

        return $towers;
    }

    private function seedFlats(array $towers): array
    {
        $flats = [];
        $types = ['1BHK' => 650, '2BHK' => 1050, '3BHK' => 1450];

        $plan = [
            'Tower A' => ['prefix' => 'A', 'floors' => range(1, 5), 'perFloor' => 2],
            'Tower B' => ['prefix' => 'B', 'floors' => range(1, 4), 'perFloor' => 2],
            'Tower C' => ['prefix' => 'C', 'floors' => range(1, 3), 'perFloor' => 2],
        ];

        foreach ($plan as $towerName => $cfg) {
            $tower = $towers[$towerName];
            $typeKeys = array_keys($types);

            foreach ($cfg['floors'] as $floor) {
                for ($unit = 1; $unit <= $cfg['perFloor']; $unit++) {
                    $flatNumber = sprintf('%s-%d0%d', $cfg['prefix'], $floor, $unit);
                    $type = $typeKeys[($floor + $unit) % count($typeKeys)];

                    $flats[] = Flat::firstOrCreate(
                        ['tower_id' => $tower->id, 'flat_number' => $flatNumber],
                        [
                            'floor_number' => $floor,
                            'type' => $type,
                            'carpet_area' => $types[$type],
                            'occupancy_status' => ($floor === 5 && $unit === 2) ? 'vacant' : 'occupied',
                            'is_active' => true,
                            'created_by' => $this->adminId,
                        ]
                    );
                }
            }
        }

        return $flats;
    }

    private function attachResidentsToFlats($residents, array $flats): void
    {
        foreach ($residents as $i => $resident) {
            $flat = $flats[$i % count($flats)] ?? null;
            if (!$flat) {
                continue;
            }

            if (!$resident->flats()->where('flat_id', $flat->id)->exists()) {
                $resident->flats()->attach($flat->id, [
                    'relation_type' => $i % 3 === 0 ? 'tenant' : 'owner',
                    'start_date' => now()->subMonths(6 + $i),
                    'is_primary' => true,
                    'is_active' => true,
                    'created_by' => $this->adminId,
                ]);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Vehicles                                                            */
    /* ------------------------------------------------------------------ */

    private function seedVehicles(Society $society, array $flats, $residents): void
    {
        $vehicles = [
            ['GJ01AB1234', 'car',     'Maruti Suzuki', 'Swift',     'White'],
            ['GJ01CD5678', 'car',     'Hyundai',       'Creta',     'Grey'],
            ['GJ01EF9012', 'bike',    'Royal Enfield', 'Classic',   'Black'],
            ['GJ05GH3456', 'scooter', 'Honda',         'Activa',    'Silver'],
            ['MH02IJ7890', 'car',     'Tata',          'Nexon EV',  'Blue'],
            ['GJ01KL2345', 'bike',    'Bajaj',         'Pulsar',    'Red'],
            ['GJ18MN6789', 'car',     'Mahindra',      'XUV700',    'Black'],
            ['GJ01OP1122', 'bicycle', 'Hero',          'Sprint',    'Green'],
        ];

        foreach ($vehicles as $i => [$reg, $type, $make, $model, $color]) {
            $flat = $flats[$i % count($flats)] ?? null;
            if (!$flat) {
                continue;
            }

            Vehicle::firstOrCreate(
                ['registration_number' => $reg],
                [
                    'society_id' => $society->id,
                    'flat_id' => $flat->id,
                    'vehicle_type' => $type,
                    'make' => $make,
                    'model' => $model,
                    'color' => $color,
                    'is_active' => true,
                    'created_by' => $this->adminId,
                ]
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /* Amenities + bookings                                                */
    /* ------------------------------------------------------------------ */

    private function seedAmenities(Society $society, array $flats, $residents): void
    {
        $definitions = [
            ['Clubhouse',        'clubhouse',  80,  '08:00:00', '22:00:00', 120, 500.00, 'Air-conditioned hall with seating for events and meetings.'],
            ['Swimming Pool',    'pool',       30,  '06:00:00', '20:00:00', 60,  null,   'Semi-Olympic pool. Swimming caps mandatory.'],
            ['Gymnasium',        'gym',        25,  '05:00:00', '22:00:00', 60,  null,   'Cardio and free-weights section with a trainer on weekday mornings.'],
            ['Badminton Court',  'badminton',  4,   '06:00:00', '21:00:00', 60,  150.00, 'Wooden indoor court. Bring your own racket and shuttle.'],
            ['Party Hall',       'party_hall', 120, '10:00:00', '23:00:00', 240, 2500.00,'Banquet hall for birthdays and society functions.'],
            ['Kids Garden',      'garden',     50,  '06:00:00', '20:00:00', 60,  null,   'Play area with swings and a walking track.'],
        ];

        $amenities = [];

        foreach ($definitions as [$name, $type, $capacity, $open, $close, $duration, $charge, $desc]) {
            $amenities[] = Amenity::firstOrCreate(
                ['society_id' => $society->id, 'name' => $name],
                [
                    'description' => $desc,
                    'type' => $type,
                    'capacity' => $capacity,
                    'opening_time' => $open,
                    'closing_time' => $close,
                    'booking_duration' => $duration,
                    'advance_booking_days' => 7,
                    'cancellation_hours' => 24,
                    'charge_per_hour' => $charge,
                    'is_active' => true,
                ]
            );
        }

        if ($residents->isEmpty()) {
            return;
        }

        $bookings = [
            [0, 2,  '18:00:00', '20:00:00', 'Birthday celebration',   'confirmed', 1000.00],
            [3, 1,  '07:00:00', '08:00:00', 'Morning doubles match',  'confirmed', 150.00],
            [4, 5,  '19:00:00', '23:00:00', 'Anniversary dinner',     'pending',   10000.00],
            [1, -3, '07:00:00', '08:00:00', 'Swim session',           'completed', null],
            [3, -1, '18:00:00', '19:00:00', 'Practice',               'cancelled', 150.00],
            [2, 0,  '06:00:00', '07:00:00', 'Weight training',        'confirmed', null],
        ];

        foreach ($bookings as $i => [$amenityIdx, $dayOffset, $start, $end, $purpose, $status, $charge]) {
            $amenity = $amenities[$amenityIdx] ?? null;
            $resident = $residents[$i % $residents->count()] ?? null;
            if (!$amenity || !$resident) {
                continue;
            }

            $flat = $resident->primaryFlat() ?? ($flats[0] ?? null);
            if (!$flat) {
                continue;
            }

            AmenityBooking::firstOrCreate(
                [
                    'amenity_id' => $amenity->id,
                    'user_id' => $resident->id,
                    'booking_date' => now()->addDays($dayOffset)->toDateString(),
                    'start_time' => $start,
                ],
                [
                    'flat_id' => $flat->id,
                    'society_id' => $society->id,
                    'end_time' => $end,
                    'purpose' => $purpose,
                    'status' => $status,
                    'total_charge' => $charge,
                ]
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /* Notices                                                             */
    /* ------------------------------------------------------------------ */

    private function seedNotices(Society $society, ?User $author): void
    {
        $authorId = $author?->id ?? $this->adminId;

        $notices = [
            [
                'title' => 'Annual General Body Meeting - 28th of this month',
                'content' => "The Annual General Body Meeting will be held in the Clubhouse at 6:30 PM.\n\nAgenda:\n1. Audited accounts for the year\n2. Maintenance revision proposal\n3. Security agency renewal\n4. Election of managing committee members\n\nQuorum is mandatory. Members unable to attend may submit a written proxy to the society office two days in advance.",
                'category' => 'meeting',
                'priority' => 'high',
                'status' => 'published',
                'is_pinned' => true,
                'published_at' => now()->subDays(3),
                'expires_at' => now()->addDays(25),
            ],
            [
                'title' => 'Water supply interruption on Saturday',
                'content' => "Overhead tank cleaning is scheduled for Saturday. Water supply will remain unavailable from 10:00 AM to 4:00 PM across all towers.\n\nResidents are requested to store sufficient water in advance. Supply will resume by evening.",
                'category' => 'maintenance',
                'priority' => 'urgent',
                'status' => 'published',
                'is_pinned' => true,
                'published_at' => now()->subDays(1),
                'expires_at' => now()->addDays(6),
            ],
            [
                'title' => 'Diwali celebration and rangoli competition',
                'content' => "The society is organising a Diwali get-together in the Kids Garden from 7:00 PM onwards.\n\nA rangoli competition will be held for residents, with prizes for the top three entries. Registration is free - please share your name and flat number with the society office.",
                'category' => 'event',
                'priority' => 'normal',
                'status' => 'published',
                'is_pinned' => false,
                'published_at' => now()->subDays(7),
                'expires_at' => now()->addDays(14),
            ],
            [
                'title' => 'Revised visitor entry procedure at the main gate',
                'content' => "With immediate effect, all visitors must be registered at the main gate before entry. The security desk will record the visitor's name, phone number and purpose, and seek approval from the concerned flat.\n\nDelivery and cab drivers will be allowed only up to the tower lobby. Your co-operation is requested.",
                'category' => 'general',
                'priority' => 'normal',
                'status' => 'published',
                'is_pinned' => false,
                'published_at' => now()->subDays(12),
                'expires_at' => now()->addDays(60),
            ],
            [
                'title' => 'Fire safety drill - schedule to be announced',
                'content' => "A mandatory fire safety drill will be conducted in co-ordination with the local fire department. The exact date and time will be circulated shortly.\n\nAll residents are expected to participate.",
                'category' => 'emergency',
                'priority' => 'high',
                'status' => 'draft',
                'is_pinned' => false,
                'published_at' => null,
                'expires_at' => null,
            ],
            [
                'title' => 'Lift maintenance completed in Tower B',
                'content' => "The annual maintenance contract work on both lifts in Tower B has been completed and the lifts are back in service. Thank you for your patience during the two-day shutdown.",
                'category' => 'maintenance',
                'priority' => 'low',
                'status' => 'archived',
                'is_pinned' => false,
                'published_at' => now()->subDays(45),
                'expires_at' => now()->subDays(15),
            ],
        ];

        foreach ($notices as $data) {
            Notice::firstOrCreate(
                ['society_id' => $society->id, 'title' => $data['title']],
                [
                    'content' => $data['content'],
                    'category' => $data['category'],
                    'priority' => $data['priority'],
                    'status' => $data['status'],
                    'visibility' => 'public',
                    'target_user_id' => null,
                    'target_role' => null,
                    'is_pinned' => $data['is_pinned'],
                    'published_at' => $data['published_at'],
                    'expires_at' => $data['expires_at'],
                    // legacy columns kept in sync with the phase-3a columns
                    'publish_date' => $data['published_at']?->toDateString(),
                    'expiry_date' => $data['expires_at']?->toDateString(),
                    'is_active' => $data['status'] !== 'archived',
                    'created_by' => $authorId,
                ]
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /* Complaints                                                          */
    /* ------------------------------------------------------------------ */

    private function seedComplaints($residents, array $flats, $staff, ?User $admin): void
    {
        if ($residents->isEmpty()) {
            return;
        }

        $adminId = $admin?->id ?? $this->adminId;
        $staffMember = $staff->first();

        // Complaint::generateTicketNumber() derives the sequence from complaints
        // created *today*. These demo rows are backdated, so that lookup always
        // returns 0 and every row would collide on CMP-<today>-0001.
        // We therefore assign ticket numbers explicitly, per backdated day.
        $ticketSequences = [];

        $complaints = [
            [
                'category' => 'plumbing',
                'subject' => 'Water leakage from bathroom ceiling',
                'description' => 'There is continuous seepage from the bathroom ceiling in my flat. The paint has started peeling and the water is dripping onto the floor. The flat above has been informed but the problem persists.',
                'priority' => 'high',
                'status' => 'in_progress',
                'age' => 4,
                'assigned' => true,
                'comments' => [
                    ['staff', 'Site inspected. Leakage is from the upstairs bathroom trap. Plumber scheduled for tomorrow morning.', false],
                    ['resident', 'Thank you. Please inform me before the plumber arrives so someone is at home.', false],
                ],
            ],
            [
                'category' => 'electrical',
                'subject' => 'Corridor light not working on 3rd floor',
                'description' => 'The common corridor light outside the lift on the 3rd floor has not been working for the past week. It is completely dark at night which is a safety concern, especially for children and senior citizens.',
                'priority' => 'medium',
                'status' => 'resolved',
                'age' => 9,
                'assigned' => true,
                'resolution' => 'Faulty LED fitting replaced with a new 18W panel. Tested and working.',
                'comments' => [
                    ['staff', 'New fitting procured from the society store. Will be installed today.', true],
                    ['staff', 'Installation done and tested. Please confirm from your side.', false],
                ],
            ],
            [
                'category' => 'security',
                'subject' => 'Unknown persons using the visitor parking',
                'description' => 'For the last few days, two vehicles that do not belong to any resident have been parked in the visitor parking area overnight. Security is not checking entry properly.',
                'priority' => 'urgent',
                'status' => 'open',
                'age' => 1,
                'assigned' => false,
                'comments' => [],
            ],
            [
                'category' => 'housekeeping',
                'subject' => 'Garbage not collected from Tower B for two days',
                'description' => 'The housekeeping staff has not collected door-to-door garbage from Tower B since Monday. The bins on the landing are overflowing and there is a bad smell in the corridor.',
                'priority' => 'high',
                'status' => 'in_progress',
                'age' => 2,
                'assigned' => true,
                'comments' => [
                    ['staff', 'Two housekeeping staff were on leave. Replacement arranged from today.', false],
                ],
            ],
            [
                'category' => 'lift',
                'subject' => 'Lift making unusual noise while descending',
                'description' => 'The lift in Tower A makes a loud grinding noise between the 4th and 2nd floor while going down. It also jerks slightly before stopping. Requesting an urgent inspection by the AMC vendor.',
                'priority' => 'urgent',
                'status' => 'disputed',
                'age' => 15,
                'assigned' => true,
                'resolution' => 'AMC vendor inspected and lubricated the guide rails.',
                'comments' => [
                    ['staff', 'Vendor visited and carried out lubrication of guide rails. Marking as resolved.', false],
                    ['resident', 'The noise has come back within three days. Reopening this - the issue is not fixed.', false],
                ],
            ],
            [
                'category' => 'parking',
                'subject' => 'Neighbouring flat parking in my allotted slot',
                'description' => 'My allotted parking slot is being used by another vehicle almost every evening. I have spoken to them directly but there has been no change. Requesting the committee to intervene.',
                'priority' => 'low',
                'status' => 'closed',
                'age' => 30,
                'assigned' => true,
                'resolution' => 'Slot numbers repainted and a warning circular issued. Both parties agreed in a joint meeting.',
                'comments' => [
                    ['staff', 'Joint meeting held with both residents. Slot markings to be repainted.', true],
                ],
            ],
            [
                'category' => 'plumbing',
                'subject' => 'Low water pressure on upper floors',
                'description' => 'Water pressure on the 4th and 5th floors is very low during peak morning hours. It takes more than ten minutes to fill a bucket.',
                'priority' => 'medium',
                'status' => 'open',
                'age' => 3,
                'assigned' => false,
                'comments' => [],
            ],
            [
                'category' => 'pest_control',
                'subject' => 'Cockroach problem in kitchen area',
                'description' => 'There has been a sharp increase in cockroaches in the kitchen and near the common drainage line. Requesting society-level pest control for the whole tower rather than individual treatment.',
                'priority' => 'medium',
                'status' => 'resolved',
                'age' => 20,
                'assigned' => true,
                'resolution' => 'Society-wide pest control carried out for all towers. Next cycle scheduled in three months.',
                'comments' => [
                    ['staff', 'Quotations taken from three vendors, approved by the committee.', true],
                    ['staff', 'Treatment completed across all towers.', false],
                ],
            ],
        ];

        foreach ($complaints as $i => $c) {
            $resident = $residents[$i % $residents->count()];
            $flat = $resident->primaryFlat() ?? ($flats[$i % count($flats)] ?? null);
            if (!$flat) {
                continue;
            }

            $existing = Complaint::where('subject', $c['subject'])->where('user_id', $resident->id)->first();
            if ($existing) {
                continue;
            }

            $createdAt = now()->subDays($c['age']);
            $ticketDate = $createdAt->format('Ymd');

            if (!isset($ticketSequences[$ticketDate])) {
                $ticketSequences[$ticketDate] = Complaint::withTrashed()
                    ->where('ticket_number', 'like', "CMP-{$ticketDate}-%")
                    ->count();
            }
            $ticketSequences[$ticketDate]++;

            $complaint = new Complaint();
            $complaint->fill([
                'ticket_number' => 'CMP-' . $ticketDate . '-'
                    . str_pad((string) $ticketSequences[$ticketDate], 4, '0', STR_PAD_LEFT),
                'user_id' => $resident->id,
                'flat_id' => $flat->id,
                'category' => $c['category'],
                'subject' => $c['subject'],
                'description' => $c['description'],
                'priority' => $c['priority'],
                'status' => $c['status'],
                'assigned_to' => $c['assigned'] ? $staffMember?->id : null,
                'assigned_at' => $c['assigned'] ? $createdAt->copy()->addHours(6) : null,
                'resolved_at' => in_array($c['status'], ['resolved', 'closed', 'disputed'], true)
                    ? $createdAt->copy()->addDays(2)
                    : null,
                'resolution_note' => $c['resolution'] ?? null,
                'created_by' => $resident->id,
            ]);
            $complaint->created_at = $createdAt;
            $complaint->updated_at = $createdAt->copy()->addDays(1);
            $complaint->save();

            foreach ($c['comments'] as $j => [$who, $text, $internal]) {
                $authorId = $who === 'staff'
                    ? ($staffMember?->id ?? $adminId)
                    : $resident->id;

                ComplaintComment::create([
                    'complaint_id' => $complaint->id,
                    'user_id' => $authorId,
                    'comment' => $text,
                    'is_internal' => $internal,
                ]);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Maintenance: policy template -> policy -> bills -> payments         */
    /* ------------------------------------------------------------------ */

    private function seedMaintenance(Society $society, array $flats): void
    {
        $template = MaintenancePolicyTemplate::firstOrCreate(
            ['name' => 'Standard Monthly - Flat Type Based'],
            [
                'billing_cycle' => 'monthly',
                'calculation_type' => 'flat_type',
                'base_amount' => 2500.00,
                'type_amounts' => ['1BHK' => 1800, '2BHK' => 2500, '3BHK' => 3400],
                'late_fee_type' => 'percentage',
                'late_fee_value' => 2.00,
                'grace_days' => 5,
                'allow_partial_payment' => true,
                'is_active' => true,
                'created_by' => $this->adminId,
            ]
        );

        MaintenancePolicy::firstOrCreate(
            ['society_id' => $society->id, 'template_id' => $template->id],
            [
                'effective_from' => now()->startOfYear(),
                'is_active' => true,
                'created_by' => $this->adminId,
            ]
        );

        $rates = ['1BHK' => 1800.00, '2BHK' => 2500.00, '3BHK' => 3400.00];

        // Bills for the last 3 months, including the current one.
        for ($monthsAgo = 2; $monthsAgo >= 0; $monthsAgo--) {
            $cursor = Carbon::now()->startOfMonth()->subMonths($monthsAgo);
            $month = $cursor->format('Y-m');
            $dueDate = $cursor->copy()->day(10);

            foreach ($flats as $idx => $flat) {
                $amount = $rates[$flat->type] ?? 2500.00;

                // Spread realistic statuses across flats and months.
                [$status, $paidRatio, $lateFee] = $this->billOutcome($monthsAgo, $idx);

                $amountPaid = round($amount * $paidRatio, 2);

                $maintenance = Maintenance::firstOrCreate(
                    ['flat_id' => $flat->id, 'month' => $month],
                    [
                        'amount' => $amount,
                        'amount_paid' => $amountPaid,
                        'due_date' => $dueDate,
                        'late_fee' => $lateFee,
                        'status' => $status,
                        'paid_date' => $amountPaid > 0 ? $dueDate->copy()->subDays(2) : null,
                        'payment_mode' => $amountPaid > 0 ? ['upi', 'net_banking', 'cash', 'cheque'][$idx % 4] : null,
                        'transaction_id' => $amountPaid > 0 ? 'TXN' . strtoupper(substr(md5($flat->id . $month), 0, 10)) : null,
                        'remarks' => $status === 'overdue' ? 'Reminder sent to resident.' : null,
                        'created_by' => $this->adminId,
                    ]
                );

                if ($amountPaid > 0 && $maintenance->wasRecentlyCreated) {
                    MaintenancePayment::create([
                        'maintenance_id' => $maintenance->id,
                        'amount_paid' => $amountPaid,
                        'payment_date' => $dueDate->copy()->subDays(2),
                        'payment_mode' => $maintenance->payment_mode,
                        'transaction_id' => $maintenance->transaction_id,
                        'remarks' => $paidRatio < 1 ? 'Partial payment received.' : 'Full payment received.',
                        'created_by' => $this->adminId,
                    ]);
                }
            }
        }
    }

    /**
     * @return array{0:string,1:float,2:float} status, paid ratio, late fee
     */
    private function billOutcome(int $monthsAgo, int $flatIndex): array
    {
        // Older months are mostly settled; the current month is mostly pending.
        if ($monthsAgo === 2) {
            return $flatIndex % 7 === 0
                ? ['overdue', 0.0, 50.00]
                : ['paid', 1.0, 0.00];
        }

        if ($monthsAgo === 1) {
            return match ($flatIndex % 4) {
                0 => ['paid', 1.0, 0.00],
                1 => ['paid', 1.0, 0.00],
                2 => ['partially_paid', 0.5, 0.00],
                default => ['overdue', 0.0, 50.00],
            };
        }

        return match ($flatIndex % 3) {
            0 => ['paid', 1.0, 0.00],
            1 => ['unpaid', 0.0, 0.00],
            default => ['unpaid', 0.0, 0.00],
        };
    }

    /* ------------------------------------------------------------------ */
    /* Visitors                                                            */
    /* ------------------------------------------------------------------ */

    private function seedVisitors(array $flats, $staff, ?User $admin): void
    {
        $approverId = $staff->first()?->id ?? $admin?->id ?? $this->adminId;
        $creatorId = $staff->first()?->id ?? $this->adminId;

        $visitors = [
            ['Ramesh Patel',   '9820011223', 'Guest visit',               'approved', -2,   true,  true],
            ['Amazon Delivery','9820044556', 'Parcel delivery',           'approved', -1,   true,  true],
            ['Dr. Meera Shah', '9820077889', 'Home visit - consultation', 'approved', -0.5, true,  false],
            ['Sunil Kumar',    '9820099001', 'AC servicing technician',   'pending',  -0.2, false, false],
            ['Priya Nair',     '9820022334', 'Relative staying overnight','pending',  -0.1, false, false],
            ['Unknown Vendor', '9820055667', 'Door-to-door sales',        'rejected', -3,   true,  false],
            ['Swiggy Delivery','9820088990', 'Food delivery',             'approved', -0.05,true,  false],
            ['Kiran Desai',    '9820033445', 'Friend visit',              'approved', -4,   true,  true],
        ];

        foreach ($visitors as $i => [$name, $phone, $purpose, $status, $daysAgo, $decided, $exited]) {
            $flat = $flats[$i % count($flats)] ?? null;
            if (!$flat) {
                continue;
            }

            $entry = now()->addDays($daysAgo);

            if (Visitor::where('phone', $phone)->where('flat_id', $flat->id)->exists()) {
                continue;
            }

            // The visitors table carries CHECK constraints on MySQL/MariaDB:
            //   pending  -> approved_by AND approved_at must be NULL
            //   approved/rejected -> both must be NOT NULL
            //   exit_time may only be set when approval_status = 'approved'
            Visitor::create([
                'flat_id' => $flat->id,
                'name' => $name,
                'phone' => $phone,
                'purpose' => $purpose,
                'entry_time' => $entry,
                'exit_time' => ($exited && $status === 'approved') ? $entry->copy()->addHours(2) : null,
                'approval_status' => $status,
                'approved_by' => $decided ? $approverId : null,
                'approved_at' => $decided ? $entry->copy()->addMinutes(5) : null,
                'remarks' => $status === 'rejected' ? 'Resident declined the entry request.' : null,
                'created_by' => $creatorId,
            ]);
        }
    }

    /* ------------------------------------------------------------------ */

    private function report(Society $society): void
    {
        $counts = [
            'Towers' => Tower::where('society_id', $society->id)->count(),
            'Flats' => Flat::whereHas('tower', fn ($q) => $q->where('society_id', $society->id))->count(),
            'Users' => User::count(),
            'Complaints' => Complaint::count(),
            'Complaint comments' => ComplaintComment::count(),
            'Notices' => Notice::where('society_id', $society->id)->count(),
            'Maintenance bills' => Maintenance::count(),
            'Maintenance payments' => MaintenancePayment::count(),
            'Visitors' => Visitor::count(),
            'Vehicles' => Vehicle::where('society_id', $society->id)->count(),
            'Amenities' => Amenity::where('society_id', $society->id)->count(),
            'Amenity bookings' => AmenityBooking::count(),
        ];

        $this->command->info('');
        $this->command->info('=== DEMO DATA SUMMARY ===');
        foreach ($counts as $label => $count) {
            $this->command->info(sprintf('  %-22s %s', $label, $count));
        }
        $this->command->info('');
    }
}
