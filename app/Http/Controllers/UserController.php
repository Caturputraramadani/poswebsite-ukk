<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // public function index()
    // {
    //     $users = User::all();
    //     return view('pages.user', compact('users'));
    // }

    

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query =  User::query();

        if ($search) {
            $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('role', 'like', '%'.$search.'%');
        }


        $users = $query->paginate($perPage)->onEachSide(1); 

        if ($request->ajax()) {
            return response()->json([
                'html' => view('users.partials.table', compact('users'))->render(),
                'pagination' => view('users.partials.pagination', [
                    'paginator' => $users,
                    'elements' => $users->links()->elements, 
                    'entries_info' => "Showing {$users->firstItem()} to {$users->lastItem()} of {$users->total()} entries"
                ])->render(),
            ]);
        }

        return view('pages.user', compact('users'));
    }


    public function save(Request $request, $id = null)
    {
        // dd($request->all());
        // Validation rules
        $request->validate([
            'email' => 'required|string|email|max:255', 
            'password' => 'required|string', 
            'name' => 'required|string|max:255', 
            'role' => 'nullable|string|in:admin,employee', 
        ]);

        // Check if updating or creating
        if ($id) {
            $user = User::findOrFail($id);
            $message = "User updated successfully";
        } else {
            $user = new User();
            $message = "New User created successfully";
        }

        // Assign fields from the request
        $user->email = $request->email;
        $user->password = $request->password;
        $user->name = $request->name;
        $user->role = $request->role;  

        // Save User
        $user->save();

        // Return with success message
        return redirect()->route('users.index')->with('success', $message);
    }

    public function destroy(User $user)
    {  
        if (!$user) {
            return response()->json([
                'error' => 'User not found.'
            ], 404);
        }

        // Check if user has associated sales
        if ($user->sales()->exists()) {
            return response()->json([
                'error' => 'Cannot delete user because they have associated sales records.'
            ], 422);
        }

        try {
            $user->delete();
            return response()->json([
                'success' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }
    

}
