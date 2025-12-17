<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\EmailLog;
use App\Models\User;
use App\Models\Organization;
use App\Services\Admin\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminCommunicationController extends Controller
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Show communication dashboard
     */
    public function index()
    {
        $templates = EmailTemplate::orderBy('category')->orderBy('name')->get();
        $recentLogs = EmailLog::with(['organization', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $stats = [
            'total_templates' => EmailTemplate::count(),
            'active_templates' => EmailTemplate::where('is_active', true)->count(),
            'emails_sent_today' => EmailLog::whereDate('created_at', today())
                ->where('status', 'sent')
                ->count(),
            'emails_failed_today' => EmailLog::whereDate('created_at', today())
                ->where('status', 'failed')
                ->count(),
        ];

        return Inertia::render('Admin/Communication/Index', [
            'templates' => $templates,
            'recentLogs' => $recentLogs,
            'stats' => $stats,
        ]);
    }

    /**
     * Show email templates list
     */
    public function templates()
    {
        $templates = EmailTemplate::orderBy('category')->orderBy('name')->get()
            ->groupBy('category');

        return Inertia::render('Admin/Communication/Templates', [
            'templates' => $templates,
            'categories' => EmailTemplate::distinct()->pluck('category'),
        ]);
    }

    /**
     * Show template editor
     */
    public function editTemplate($id)
    {
        $template = EmailTemplate::findOrFail($id);

        return Inertia::render('Admin/Communication/EditTemplate', [
            'template' => $template,
        ]);
    }

    /**
     * Update email template
     */
    public function updateTemplate(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'category' => 'required|string|max:100',
            'is_active' => 'boolean',
            'variables' => 'nullable|array',
        ]);

        // Handle image uploads in body
        $body = $validated['body'];
        if ($request->has('images')) {
            foreach ($request->images as $imageData) {
                if (isset($imageData['file']) && isset($imageData['placeholder'])) {
                    // Upload image
                    $imagePath = $this->uploadEmailImage($imageData['file'], $template->slug);
                    // Replace placeholder with actual image URL
                    $body = str_replace(
                        $imageData['placeholder'],
                        Storage::url($imagePath),
                        $body
                    );
                }
            }
        }

        $template->update([
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body' => $body,
            'category' => $validated['category'],
            'is_active' => $validated['is_active'] ?? true,
            'variables' => $validated['variables'] ?? [],
        ]);

        return redirect()->back()->with('success', 'Template updated successfully.');
    }

    /**
     * Upload image for email template
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048', // 2MB max
            'template_slug' => 'nullable|string',
        ]);

        $templateSlug = $request->template_slug ?? 'general';
        $path = $this->uploadEmailImage($request->file('image'), $templateSlug);

        return response()->json([
            'success' => true,
            'url' => Storage::url($path),
            'path' => $path,
        ]);
    }

    /**
     * Helper to upload email image
     */
    protected function uploadEmailImage($file, string $templateSlug): string
    {
        $filename = Str::slug($templateSlug) . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('email-images', $filename, 'public');
    }

    /**
     * Show send email page
     */
    public function sendEmail()
    {
        $templates = EmailTemplate::where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $organizations = Organization::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $users = User::whereHas('organizations')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Admin/Communication/SendEmail', [
            'templates' => $templates,
            'organizations' => $organizations,
            'users' => $users,
        ]);
    }

    /**
     * Send email to users
     */
    public function sendBulkEmail(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'nullable|exists:email_templates,id',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'recipient_type' => 'required|in:all_users,selected_users,selected_organizations,all_organizations',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'organization_ids' => 'nullable|array',
            'organization_ids.*' => 'exists:organizations,id',
            'variables' => 'nullable|array',
        ]);

        $recipients = $this->getRecipients($validated);
        
        if (empty($recipients)) {
            return back()->withErrors(['recipient_type' => 'No recipients found.']);
        }

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                $data = $validated['variables'] ?? [];
                $data['user_name'] = $recipient['name'];
                $data['user_email'] = $recipient['email'];

                if ($validated['template_id']) {
                    $template = EmailTemplate::find($validated['template_id']);
                    $rendered = $template->render($data);
                    $subject = $rendered['subject'];
                    $body = $rendered['body'];
                } else {
                    $subject = $validated['subject'];
                    $body = $validated['body'];
                    // Replace variables in custom email
                    foreach ($data as $key => $value) {
                        $body = str_replace('{{' . $key . '}}', $value, $body);
                        $subject = str_replace('{{' . $key . '}}', $value, $subject);
                    }
                }

                $success = $this->emailService->send(
                    to: $recipient['email'],
                    subject: $subject,
                    body: $body,
                    organization: $recipient['organization'] ?? null,
                    user: $recipient['user'] ?? null
                );

                if ($success) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                \Log::error('Bulk email send failed', [
                    'recipient' => $recipient['email'],
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        return redirect()->back()->with('success', "Emails sent: {$sent} successful, {$failed} failed.");
    }

    /**
     * Get recipients based on selection
     */
    protected function getRecipients(array $validated): array
    {
        $recipients = [];

        switch ($validated['recipient_type']) {
            case 'all_users':
                $users = User::whereHas('organizations')->get();
                foreach ($users as $user) {
                    $recipients[] = [
                        'email' => $user->email,
                        'name' => $user->name,
                        'user' => $user,
                        'organization' => $user->organizations()->first(),
                    ];
                }
                break;

            case 'selected_users':
                $users = User::whereIn('id', $validated['user_ids'] ?? [])->get();
                foreach ($users as $user) {
                    $recipients[] = [
                        'email' => $user->email,
                        'name' => $user->name,
                        'user' => $user,
                        'organization' => $user->organizations()->first(),
                    ];
                }
                break;

            case 'selected_organizations':
                $organizations = Organization::whereIn('id', $validated['organization_ids'] ?? [])->get();
                foreach ($organizations as $org) {
                    foreach ($org->users as $user) {
                        $recipients[] = [
                            'email' => $user->email,
                            'name' => $user->name,
                            'user' => $user,
                            'organization' => $org,
                        ];
                    }
                }
                break;

            case 'all_organizations':
                $organizations = Organization::where('status', 'active')->get();
                foreach ($organizations as $org) {
                    foreach ($org->users as $user) {
                        $recipients[] = [
                            'email' => $user->email,
                            'name' => $user->name,
                            'user' => $user,
                            'organization' => $org,
                        ];
                    }
                }
                break;
        }

        // Remove duplicates by email
        $unique = [];
        $seen = [];
        foreach ($recipients as $recipient) {
            if (!in_array($recipient['email'], $seen)) {
                $unique[] = $recipient;
                $seen[] = $recipient['email'];
            }
        }

        return $unique;
    }

    /**
     * Show email logs
     */
    public function logs(Request $request)
    {
        $query = EmailLog::with(['organization', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('to', 'like', '%' . $request->search . '%')
                  ->orWhere('subject', 'like', '%' . $request->search . '%');
            });
        }

        $logs = $query->paginate(50);

        return Inertia::render('Admin/Communication/Logs', [
            'logs' => $logs,
        ]);
    }

    /**
     * Preview template
     */
    public function previewTemplate(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);
        
        $data = $request->get('data', []);
        $rendered = $template->render($data);

        return response()->json([
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
        ]);
    }
}
