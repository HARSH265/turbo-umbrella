<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\Role;
use App\Services\ActivityLogService;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * UserController
 * 
 * Manages user accounts and role assignments
 */
class UserController extends Controller
{
    protected ActivityLogService $activityLog;
    protected FileService $fileService;

    public function __construct(ActivityLogService $activityLog, FileService $fileService)
    {
        $this->activityLog = $activityLog;
        $this->fileService = $fileService;
        
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['create', 'store']);
        $this->middleware('permission:users.update')->only(['edit', 'update', 'toggleActive']);
        $this->middleware('permission:users.delete')->only('destroy');
    }

    /**
     * Display list of users
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Filter by role
        if ($request->filled('role')) {
            $query->withRole($request->role);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name, email, or phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('email', 'LIKE', "%$search%")
                  ->orWhere('phone', 'LIKE', "%$search%");
            });
        }

        $users = $query->latest()->paginate(50)->withQueryString();
        $roles = Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Show form to create user
     */
    public function create()
    {
        $roles = Role::all();

        return view('users.create', compact('roles'));
    }

    /**
     * Store new user
     */
    public function store(StoreUserRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['created_by'] = Auth::id();
            $data['password'] = Hash::make($data['password']);

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                $file = $this->fileService->upload(
                    $request->file('profile_photo'),
                    'users',
                    0 // Temporary, will update after user creation
                );
                $data['profile_photo'] = $file->path;
            }

            $user = User::create($data);

            // Update file entity_id if photo was uploaded
            if (isset($file)) {
                $file->update(['entity_id' => $user->id]);
            }

            // Assign role
            $user->roles()->attach($request->role_id);

            $this->activityLog->logCreate('user', $user->id, $user->toArray());

            DB::commit();

            return redirect()
                ->route('users.show', $user)
                ->with('success', 'User created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create user: ' . $e->getMessage()]);
        }
    }

    /**
     * Display user details
     */
    public function show(User $user)
    {
        $user->load(['roles', 'activeFlats.tower', 'complaints' => fn($q) => $q->latest()->take(5)]);

        return view('users.show', compact('user'));
    }

    /**
     * Show form to edit user
     */
    public function edit(User $user)
    {
        $roles = Role::all();

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|string|regex:/^[0-9]{10}$/|unique:users,phone,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'password' => 'nullable|confirmed|min:8',
        ]);

        DB::beginTransaction();
        try {
            $oldData = $user->toArray();
            
            $data = $request->only(['name', 'email', 'phone']);
            $data['updated_by'] = Auth::id();

            // Update password if provided
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                // Delete old photo
                if ($user->profile_photo) {
                    $oldFile = \App\Models\File::where('path', $user->profile_photo)->first();
                    if ($oldFile) {
                        $this->fileService->delete($oldFile->id, true);
                    }
                }

                $file = $this->fileService->upload(
                    $request->file('profile_photo'),
                    'users',
                    $user->id
                );
                $data['profile_photo'] = $file->path;
            }

            $user->update($data);

            // Update role
            $user->roles()->sync([$request->role_id]);

            $this->activityLog->logUpdate('user', $user->id, $oldData, $user->fresh()->toArray());

            DB::commit();

            return redirect()
                ->route('users.show', $user)
                ->with('success', 'User updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update user.']);
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleActive(User $user)
    {
        // Prevent deactivating yourself
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot deactivate your own account.']);
        }

        try {
            $user->update([
                'is_active' => !$user->is_active,
                'updated_by' => Auth::id(),
            ]);

            $status = $user->is_active ? 'activated' : 'deactivated';

            return back()->with('success', "User {$status} successfully.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update user status.']);
        }
    }

    /**
     * Delete user (soft delete)
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        // Prevent deleting users with active flat assignments
        if ($user->activeFlats()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete user with active flat assignments.']);
        }

        try {
            $this->activityLog->logDelete('user', $user->id, $user->toArray());
            $user->delete();

            return redirect()
                ->route('users.index')
                ->with('success', 'User deleted successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete user.']);
        }
    }
}
