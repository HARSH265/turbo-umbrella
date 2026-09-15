# SSMS UI Conventions

One way to build each thing. Use these classes instead of writing Tailwind utilities by
hand — that is what kept the interface consistent, and hand-written variants are what
made it inconsistent in the first place.

Everything below is defined in [`resources/css/app.css`](../resources/css/app.css).

---

## Why this exists

Before this pass the app had **three competing button styles**:

| Source | Style |
|---|---|
| `.btn` CSS classes | emerald, `rounded-lg`, sentence case |
| Hand-written markup (98 instances) | black `bg-gray-900`, `rounded-lg` |
| Breeze Blade components | `bg-gray-800`, **indigo** focus rings, uppercase, `rounded-md` |

Two different colours were being used for the same "primary action" role, which is why
screens felt unrelated to each other. Form fields had the same problem: 26 used
`.form-input`, 71 were hand-written, and labels were written three different ways.

The Blade components now render these same classes, so there is a single source of truth.

---

## Buttons

Pick by **role**, never by colour.

| Class | Use for | Looks like |
|---|---|---|
| `.btn .btn-primary` | The one main action on a screen — save, create, submit | Solid emerald |
| `.btn .btn-secondary` | Supporting actions — back, cancel, view, edit | White, grey border |
| `.btn .btn-danger` | Destructive — delete, deactivate | Solid red |
| `.btn .btn-ghost` | Inline action inside a table row or card | Text only |

Modifiers: `.btn-sm` (compact), `.btn-lg` (prominent), `.btn-block` (full width).

```blade
<a href="{{ route('complaints.create') }}" class="btn btn-primary">New Complaint</a>
<a href="{{ route('complaints.index') }}" class="btn btn-secondary">← Back to List</a>
<button class="btn btn-danger btn-sm">Delete</button>
```

In Blade, `<x-primary-button>`, `<x-secondary-button>` and `<x-danger-button>` render
the same classes — use whichever fits.

**One primary per screen.** If two buttons are both primary, one of them isn't.

`.btn` already carries `justify-center`, so a stacked or full-width button centres its
label. Hand-written buttons did not, which is why "Back to List" sat left-aligned in a
full-width box on phones.

---

## Forms

```blade
<div class="form-group">
    <label class="form-label" for="name">
        Policy name <span class="form-label-optional">(optional)</span>
    </label>
    <input id="name" type="text" name="name" class="form-input">
    <p class="form-help">Shown at the top of the policy document.</p>
    <p class="form-error">@error('name'){{ $message }}@enderror</p>
</div>
```

| Class | Use for |
|---|---|
| `.form-group` | Wraps a label + field + help text |
| `.form-label` | Every field label |
| `.form-input` | Text, number, date, email inputs |
| `.form-select` | Selects |
| `.form-textarea` | Textareas |
| `.form-checkbox` | Checkboxes and radios |
| `.form-help` | Hint under a field |
| `.form-error` | Validation message |

Focus rings are emerald everywhere. Don't add your own `focus:ring-*`.

---

## Badges

Status pills. Pick by meaning, not colour.

| Class | Meaning |
|---|---|
| `.badge .badge-success` | Active, paid, approved, resolved |
| `.badge .badge-warning` | Pending, in progress, partially paid |
| `.badge .badge-danger` | Rejected, overdue, deleted |
| `.badge .badge-info` | Neutral informational state |
| `.badge .badge-gray` | Inactive, closed, archived |

```blade
<span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
    {{ $user->is_active ? 'Active' : 'Inactive' }}
</span>
```

---

## Page headers

```blade
<div class="page-header">
    <div class="min-w-0">
        <h1 class="page-header-title">Vehicles</h1>
        <p class="page-header-subtitle">Manage registered vehicles</p>
    </div>
    <div class="page-header-actions">
        <a href="..." class="btn btn-primary">Register Vehicle</a>
    </div>
</div>
```

`.page-header` stacks below `sm` and goes side by side above it.
`.page-header-actions` wraps, so several buttons never push the page wider than the
viewport. Always use it when a header has more than one action.

---

## Lists and tables

Always use `<x-data-table>`. Never hand-write a `<table>` for a listing.

```blade
<x-data-table :headers="['Flat', 'Month', 'Amount', 'Status', 'Actions']" :data="$bills">
    @forelse($bills as $bill)
        <tr>
            <td>{{ $bill->flat->flat_number }}</td>
            ...
            <td class="text-right">
                <a href="..." class="btn btn-ghost btn-sm">View</a>
            </td>
        </tr>
    @empty
    @endforelse
</x-data-table>
```

The component gives you, for free:

- **Cards on phones** — below `md` each row becomes a labelled card. Labels come from
  `:headers` via `--col-N` custom properties, so cells need no `data-label`.
- **Tappable rows** — on phones the whole card opens the row's first view link
  ([`resources/js/tappable-rows.js`](../resources/js/tappable-rows.js)).
- **Consistent pagination** via `<x-pagination>`, including the record count.
- **Empty state** through the `emptyMessage` prop.

If you genuinely need a bespoke table (a sub-table on a detail page), wrap it in
`<div class="overflow-x-auto">` or it will push the whole page wider than a phone screen.

Page size comes from `config('pagination.per_page')` — never hard-code a number.

---

## Documents

For pages read rather than operated — currently the maintenance policy.

| Class | Use for |
|---|---|
| `.doc-heading` | Section heading inside a document |
| `.doc-body` | Body paragraph, capped at a readable measure |

---

## Layout rules that are easy to get wrong

- **Never** put a `flex` row of buttons without `flex-wrap`. Four buttons at 375px will
  push the page sideways.
- A `<table>` with `min-w-full` and no scroll container drags the whole page wide.
- Avoid `uppercase` with `tracking-widest` on buttons — it stretched "View All Notices"
  so wide it wrapped inside its own box.
- Check a new screen at **375px** before calling it done. The quickest check is
  `document.documentElement.scrollWidth === clientWidth`.

---

## Checking your work

```bash
npm run build
php artisan test
```

Two smoke tests guard the whole surface:
`tests/Feature/Smoke/RouteSweepTest.php` walks every GET route as every role and fails
on any 5xx; `tests/Feature/Smoke/DashboardRenderTest.php` renders every role's dashboard.

---

## Form controls in a header or toolbar

`.form-input` and `.form-select` are `w-full`, which is right in a form column but wrong
in a page header — a full-width select eats the whole row and pushes the button beside it
onto a second line, over the content below.

Add `.form-inline` for a control that lives in a toolbar:

```blade
<div class="page-header-actions">
    <form method="POST" action="..." class="flex flex-wrap items-center gap-2">
        @csrf
        <select name="society_id" class="form-select form-inline">...</select>
        <button class="btn btn-secondary">Generate Current Month</button>
    </form>
    <a href="..." class="btn btn-primary">Create Policy</a>
</div>
```
