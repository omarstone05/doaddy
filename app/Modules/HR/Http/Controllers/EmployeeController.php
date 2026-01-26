<?php

namespace App\Modules\HR\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Notification;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Resolve current organization id (session -> legacy field -> first organization)
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organizations()->first()?->id;
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to access HR features.');
        }
        
        $query = TeamMember::where('organization_id', $organizationId)
            ->with(['department', 'user']);
        
        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%");
            });
        }
        
        // Department filter
        if ($request->has('department_id') && $request->department_id) {
            $query->where('department_id', $request->department_id);
        }
        
        // Status filter
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active === 'true' || $request->is_active === '1');
        }
        
        // Employment type filter
        if ($request->has('employment_type') && $request->employment_type) {
            $query->where('employment_type', $request->employment_type);
        }
        
        // Sort
        $sortBy = $request->get('sort_by', 'first_name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);
        
        $employees = $query->paginate($request->get('per_page', 20))->through(function ($member) {
            return [
                'id' => $member->id,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'full_name' => $member->full_name,
                'email' => $member->email,
                'phone' => $member->phone,
                'employee_number' => $member->employee_number,
                'job_title' => $member->job_title,
                'department' => $member->department ? [
                    'id' => $member->department->id,
                    'name' => $member->department->name,
                ] : null,
                'hire_date' => optional($member->hire_date)->toDateString(),
                'employment_type' => $member->employment_type,
                'salary' => $member->salary,
                'is_active' => (bool) $member->is_active,
                'has_user_account' => $member->user !== null,
            ];
        });
        
        $departments = Department::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return Inertia::render('HR/Employees/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => $request->only(['search', 'department_id', 'is_active', 'employment_type', 'sort_by', 'sort_dir']),
        ]);
    }

    /**
     * Show the form for creating a new employee
     */
    public function create()
    {
        $user = Auth::user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organizations()->first()?->id;
        
        $departments = Department::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return Inertia::render('HR/Employees/Create', [
            'departments' => $departments,
        ]);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organizations()->first()?->id;
        if (!$organizationId) {
            abort(403, 'You must belong to an organization to add employees.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:255|unique:team_members,employee_number',
            'hire_date' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'salary' => 'nullable|numeric|min:0',
            'employment_type' => 'nullable|in:full_time,part_time,contract,freelance',
            'address' => 'nullable|array',
            'emergency_contact' => 'nullable|array',
            // Bank Details
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_branch_code' => 'nullable|string|max:255',
            'bank_sort_code' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $attributes = [
            'id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'employee_number' => $validated['employee_number'] ?? null,
            'hire_date' => $validated['hire_date'] ?? null,
            'job_title' => $validated['job_title'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'salary' => $validated['salary'] ?? null,
            'employment_type' => $validated['employment_type'] ?? null,
            'address' => $validated['address'] ?? null,
            'emergency_contact' => $validated['emergency_contact'] ?? null,
            // Bank Details
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_name' => $validated['bank_account_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'bank_branch_code' => $validated['bank_branch_code'] ?? null,
            'bank_sort_code' => $validated['bank_sort_code'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];

        $employee = TeamMember::create($attributes);

        // Send email notification if email is provided
        if (!empty($validated['email'])) {
            try {
                $emailService = app(\App\Services\Admin\EmailService::class);
                $organization = \App\Models\Organization::find($organizationId);
                
                $existingUser = User::where('email', $validated['email'])->first();
                
                if ($existingUser) {
                    $emailService->send(
                        to: $validated['email'],
                        subject: 'You\'ve been added to ' . $organization->name . ' on Addy Business',
                        body: "Hi {$existingUser->name},\n\nYou've been added as an employee to {$organization->name} on Addy Business.\n\nYou can now access employee features in your dashboard.\n\nBest regards,\nAddy Business Team",
                        templateSlug: 'team_invitation',
                        organization: $organization,
                        user: $existingUser
                    );

                    // Drop an in-app notification for immediate acceptance
                    $notification = Notification::createForUser(
                        $existingUser->id,
                        $organization->id,
                        'invitation',
                        "You're invited to {$organization->name}",
                        'Accept to join this organization instantly.'
                    );
                    $notification->update([
                        'action_url' => route('notifications.accept', $notification->id),
                    ]);
                } else {
                    $tempUser = new User([
                        'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                        'email' => $validated['email'],
                    ]);
                    $emailService->sendTeamMemberInvitation($tempUser, $organization, Auth::user()->name);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to send employee creation email', [
                    'employee_id' => $employee->id,
                    'email' => $validated['email'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('hr.employees.show', $employee->id)->with('message', 'Employee created successfully');
    }

    /**
     * Display the specified employee
     */
    public function show($id)
    {
        $user = Auth::user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organizations()->first()?->id;
        
        $employee = TeamMember::where('organization_id', $organizationId)
            ->with([
                'department',
                'user',
                'leaveRequests' => function ($query) {
                    $query->latest()->limit(5);
                },
                'sales' => function ($query) {
                    $query->latest()->limit(5);
                },
                'attachments.uploadedBy',
                'documents.createdBy',
                'documents.attachments',
            ])
            ->findOrFail($id);
        
        // Calculate tenure
        $tenure = null;
        if ($employee->hire_date) {
            $hireDate = \Carbon\Carbon::parse($employee->hire_date);
            $now = \Carbon\Carbon::now();
            $years = $now->diffInYears($hireDate);
            $months = $now->diffInMonths($hireDate) % 12;
            $tenure = [
                'years' => $years,
                'months' => $months,
                'formatted' => $years > 0 ? "{$years} year" . ($years > 1 ? 's' : '') . ($months > 0 ? " {$months} month" . ($months > 1 ? 's' : '') : '') : "{$months} month" . ($months > 1 ? 's' : ''),
            ];
        }
        
        return Inertia::render('HR/Employees/Show', [
            'employee' => [
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'employee_number' => $employee->employee_number,
                'job_title' => $employee->job_title,
                'department' => $employee->department ? [
                    'id' => $employee->department->id,
                    'name' => $employee->department->name,
                ] : null,
                'hire_date' => optional($employee->hire_date)->toDateString(),
                'employment_type' => $employee->employment_type,
                'salary' => $employee->salary,
                'address' => $employee->address,
                'emergency_contact' => $employee->emergency_contact,
                // Bank Details
                'bank_name' => $employee->bank_name,
                'bank_account_name' => $employee->bank_account_name,
                'bank_account_number' => $employee->bank_account_number,
                'bank_branch_code' => $employee->bank_branch_code,
                'bank_sort_code' => $employee->bank_sort_code,
                'is_active' => (bool) $employee->is_active,
                'has_user_account' => $employee->user !== null,
                'user' => $employee->user ? [
                    'id' => $employee->user->id,
                    'name' => $employee->user->name,
                    'email' => $employee->user->email,
                ] : null,
                'tenure' => $tenure,
                'recent_leave_requests' => $employee->leaveRequests->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'leave_type' => $request->leaveType?->name,
                        'start_date' => $request->start_date->toDateString(),
                        'end_date' => $request->end_date->toDateString(),
                        'status' => $request->status,
                    ];
                }),
                'recent_sales' => $employee->sales->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'sale_number' => $sale->sale_number,
                        'total_amount' => $sale->total_amount,
                        'created_at' => $sale->created_at->toDateString(),
                    ];
                }),
                'attachments' => $employee->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'file_path' => $attachment->file_path,
                        'mime_type' => $attachment->mime_type,
                        'file_size' => $attachment->file_size,
                        'uploaded_by' => $attachment->uploadedBy?->name,
                    ];
                }),
                'documents' => $employee->documents->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'name' => $document->name,
                        'category' => $document->category,
                        'status' => $document->status,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified employee
     */
    public function edit($id)
    {
        $user = Auth::user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organizations()->first()?->id;
        
        $employee = TeamMember::where('organization_id', $organizationId)
            ->with('department')
            ->findOrFail($id);
        
        $departments = Department::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return Inertia::render('HR/Employees/Edit', [
            'employee' => [
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'employee_number' => $employee->employee_number,
                'job_title' => $employee->job_title,
                'department_id' => $employee->department_id,
                'hire_date' => optional($employee->hire_date)->toDateString(),
                'employment_type' => $employee->employment_type,
                'salary' => $employee->salary,
                'address' => $employee->address ?? [],
                'emergency_contact' => $employee->emergency_contact ?? [],
                // Bank Details
                'bank_name' => $employee->bank_name,
                'bank_account_name' => $employee->bank_account_name,
                'bank_account_number' => $employee->bank_account_number,
                'bank_branch_code' => $employee->bank_branch_code,
                'bank_sort_code' => $employee->bank_sort_code,
                'is_active' => $employee->is_active,
            ],
            'departments' => $departments,
        ]);
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organizations()->first()?->id;
        
        $employee = TeamMember::where('organization_id', $organizationId)
            ->findOrFail($id);
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:255|unique:team_members,employee_number,' . $id,
            'hire_date' => 'nullable|date',
            'job_title' => 'nullable|string|max:255',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'salary' => 'nullable|numeric|min:0',
            'employment_type' => 'nullable|in:full_time,part_time,contract,freelance',
            'address' => 'nullable|array',
            'emergency_contact' => 'nullable|array',
            // Bank Details
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_branch_code' => 'nullable|string|max:255',
            'bank_sort_code' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        
        // Track changes for email notification
        $oldValues = $employee->only(array_keys($validated));
        $changes = [];
        foreach ($validated as $key => $value) {
            if (isset($oldValues[$key]) && $oldValues[$key] != $value) {
                $fieldName = ucfirst(str_replace('_', ' ', $key));
                $changes[$fieldName] = $value;
            }
        }
        
        $employee->update($validated);
        
        // Send email notification if profile was updated and employee has email and user
        if (!empty($changes) && $employee->email && $employee->user) {
            try {
                $emailService = app(\App\Services\Admin\EmailService::class);
                $organization = \App\Models\Organization::find($organizationId);
                
                $changesList = [];
                foreach ($changes as $field => $value) {
                    $changesList[] = "- {$field}: {$value}";
                }
                
                $emailService->send(
                    to: $employee->email,
                    subject: 'Your employee profile has been updated',
                    body: "Hi {$employee->user->name},\n\nYour employee profile information has been updated:\n\n" . implode("\n", $changesList) . "\n\nIf you didn't make these changes, please contact your administrator.\n\nBest regards,\nAddy Business Team",
                    templateSlug: 'profile_updated',
                    organization: $organization,
                    user: $employee->user
                );
            } catch (\Exception $e) {
                \Log::warning('Failed to send employee update email', [
                    'employee_id' => $employee->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return redirect()->route('hr.employees.show', $id)->with('message', 'Employee updated successfully');
    }

    /**
     * Remove the specified employee
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organizations()->first()?->id;
        
        $employee = TeamMember::where('organization_id', $organizationId)
            ->findOrFail($id);
        
        // Check if employee has sales records
        if ($employee->sales()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete employee that has sales records.']);
        }
        
        // Check if employee has leave requests
        if ($employee->leaveRequests()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete employee that has leave requests.']);
        }
        
        $employee->delete();
        
        return redirect()->route('hr.employees.index')->with('message', 'Employee deleted successfully');
    }

    /**
     * Download CSV template for employee import
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employee_import_template.csv"',
        ];

        $columns = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'employee_number',
            'job_title',
            'department',
            'hire_date',
            'employment_type',
            'salary',
            'bank_name',
            'bank_account_name',
            'bank_account_number',
            'bank_branch_code',
            'bank_sort_code',
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            
            // Write header row
            fputcsv($file, $columns);
            
            // Write example row
            fputcsv($file, [
                'John',
                'Doe',
                'john.doe@example.com',
                '+260971234567',
                'EMP001',
                'Software Developer',
                'Engineering',
                '2024-01-15',
                'full_time',
                '5000.00',
                'First National Bank',
                'John Doe',
                '1234567890',
                '260001',
                '123456',
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import employees from CSV
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $user = Auth::user();
        $organizationId = session('current_organization_id') ?? $user->current_organization_id ?? $user->organizations()->first()?->id;
        
        if (!$organizationId) {
            return back()->withErrors(['error' => 'You must belong to an organization to import employees.']);
        }

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        try {
            if (($handle = fopen($path, 'r')) !== false) {
                // Read header row
                $header = fgetcsv($handle, 1000, ',');
                $rowNumber++;
                
                // Normalize header names
                $header = array_map(function($col) {
                    return strtolower(trim(str_replace(' ', '_', $col)));
                }, $header);

                // Map expected columns
                $columnMap = [
                    'first_name' => array_search('first_name', $header),
                    'last_name' => array_search('last_name', $header),
                    'email' => array_search('email', $header),
                    'phone' => array_search('phone', $header),
                    'employee_number' => array_search('employee_number', $header),
                    'job_title' => array_search('job_title', $header),
                    'department' => array_search('department', $header),
                    'hire_date' => array_search('hire_date', $header),
                    'employment_type' => array_search('employment_type', $header),
                    'salary' => array_search('salary', $header),
                    'bank_name' => array_search('bank_name', $header),
                    'bank_account_name' => array_search('bank_account_name', $header),
                    'bank_account_number' => array_search('bank_account_number', $header),
                    'bank_branch_code' => array_search('bank_branch_code', $header),
                    'bank_sort_code' => array_search('bank_sort_code', $header),
                ];

                // Check required columns
                if ($columnMap['first_name'] === false || $columnMap['last_name'] === false) {
                    fclose($handle);
                    return back()->withErrors(['error' => 'CSV must contain first_name and last_name columns.']);
                }

                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    try {
                        $firstName = $columnMap['first_name'] !== false ? trim($row[$columnMap['first_name']] ?? '') : '';
                        $lastName = $columnMap['last_name'] !== false ? trim($row[$columnMap['last_name']] ?? '') : '';
                        
                        // Skip empty rows
                        if (empty($firstName) && empty($lastName)) {
                            $rowNumber++;
                            continue;
                        }

                        if (empty($firstName) || empty($lastName)) {
                            $errors[] = "Row {$rowNumber}: First name and last name are required.";
                            $rowNumber++;
                            continue;
                        }

                        // Get department if provided
                        $departmentId = null;
                        if ($columnMap['department'] !== false && !empty($row[$columnMap['department']])) {
                            $departmentName = trim($row[$columnMap['department']]);
                            $department = Department::where('organization_id', $organizationId)
                                ->where('name', 'like', $departmentName)
                                ->first();
                            
                            if (!$department) {
                                // Create department if it doesn't exist
                                $department = Department::create([
                                    'id' => (string) Str::uuid(),
                                    'organization_id' => $organizationId,
                                    'name' => $departmentName,
                                    'is_active' => true,
                                ]);
                            }
                            $departmentId = $department->id;
                        }

                        // Parse employment type
                        $employmentType = null;
                        if ($columnMap['employment_type'] !== false && !empty($row[$columnMap['employment_type']])) {
                            $type = strtolower(trim($row[$columnMap['employment_type']]));
                            $validTypes = ['full_time', 'part_time', 'contract', 'freelance'];
                            $type = str_replace([' ', '-'], '_', $type);
                            if (in_array($type, $validTypes)) {
                                $employmentType = $type;
                            }
                        }

                        // Parse hire date
                        $hireDate = null;
                        if ($columnMap['hire_date'] !== false && !empty($row[$columnMap['hire_date']])) {
                            try {
                                $hireDate = \Carbon\Carbon::parse($row[$columnMap['hire_date']])->toDateString();
                            } catch (\Exception $e) {
                                // Invalid date, skip
                            }
                        }

                        // Check for duplicate employee number
                        $employeeNumber = $columnMap['employee_number'] !== false ? trim($row[$columnMap['employee_number']] ?? '') : null;
                        if ($employeeNumber) {
                            $existing = TeamMember::where('organization_id', $organizationId)
                                ->where('employee_number', $employeeNumber)
                                ->exists();
                            if ($existing) {
                                $errors[] = "Row {$rowNumber}: Employee number '{$employeeNumber}' already exists.";
                                $rowNumber++;
                                continue;
                            }
                        }

                        // Create employee
                        TeamMember::create([
                            'id' => (string) Str::uuid(),
                            'organization_id' => $organizationId,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'email' => $columnMap['email'] !== false ? trim($row[$columnMap['email']] ?? '') : null,
                            'phone' => $columnMap['phone'] !== false ? trim($row[$columnMap['phone']] ?? '') : null,
                            'employee_number' => $employeeNumber ?: null,
                            'job_title' => $columnMap['job_title'] !== false ? trim($row[$columnMap['job_title']] ?? '') : null,
                            'department_id' => $departmentId,
                            'hire_date' => $hireDate,
                            'employment_type' => $employmentType,
                            'salary' => $columnMap['salary'] !== false && is_numeric($row[$columnMap['salary']] ?? null) 
                                ? floatval($row[$columnMap['salary']]) 
                                : null,
                            // Bank Details
                            'bank_name' => $columnMap['bank_name'] !== false ? trim($row[$columnMap['bank_name']] ?? '') : null,
                            'bank_account_name' => $columnMap['bank_account_name'] !== false ? trim($row[$columnMap['bank_account_name']] ?? '') : null,
                            'bank_account_number' => $columnMap['bank_account_number'] !== false ? trim($row[$columnMap['bank_account_number']] ?? '') : null,
                            'bank_branch_code' => $columnMap['bank_branch_code'] !== false ? trim($row[$columnMap['bank_branch_code']] ?? '') : null,
                            'bank_sort_code' => $columnMap['bank_sort_code'] !== false ? trim($row[$columnMap['bank_sort_code']] ?? '') : null,
                            'is_active' => true,
                        ]);

                        $imported++;
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    }
                    
                    $rowNumber++;
                }
                
                fclose($handle);
            }
        } catch (\Exception $e) {
            \Log::error('CSV import error', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to process CSV file: ' . $e->getMessage()]);
        }

        $message = "Successfully imported {$imported} employee(s).";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " row(s) had errors.";
        }

        if (!empty($errors)) {
            return back()
                ->with('message', $message)
                ->with('import_errors', array_slice($errors, 0, 10)); // Limit to first 10 errors
        }

        return back()->with('message', $message);
    }
}
