<?php

namespace App\Services\Admin;

use App\Models\EmailTemplate;
use App\Models\EmailLog;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Mail;
use App\Mail\TemplateMail;

class EmailService
{
    /**
     * Send email using template
     */
    public function sendFromTemplate(
        string $templateSlug,
        string|array $to,
        array $data = [],
        ?Organization $organization = null,
        ?User $user = null
    ): bool {
        $template = EmailTemplate::getBySlug($templateSlug);

        if (!$template) {
            throw new \Exception("Email template '{$templateSlug}' not found");
        }

        $rendered = $template->render($data);

        return $this->send(
            to: $to,
            subject: $rendered['subject'],
            body: $rendered['body'],
            templateSlug: $templateSlug,
            organization: $organization,
            user: $user
        );
    }

    /**
     * Send email
     */
    public function send(
        string|array $to,
        string $subject,
        string $body,
        ?string $templateSlug = null,
        ?string $cc = null,
        ?string $bcc = null,
        ?Organization $organization = null,
        ?User $user = null
    ): bool {
        $to = is_array($to) ? $to[0] : $to;

        // Log email
        $log = EmailLog::create([
            'organization_id' => $organization?->id,
            'user_id' => $user?->id,
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'subject' => $subject,
            'body' => $body,
            'template_slug' => $templateSlug,
            'status' => 'pending',
        ]);

        try {
            Mail::to($to)->send(new TemplateMail($subject, $body));
            
            $log->markAsSent();
            return true;

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            \Log::error('Email sending failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail(User $user, Organization $organization): bool
    {
        return $this->sendFromTemplate(
            'welcome',
            $user->email,
            [
                'user_name' => $user->name,
                'organization_name' => $organization->name,
            ],
            $organization,
            $user
        );
    }

    /**
     * Send team member invitation email
     */
    public function sendTeamMemberInvitation(User $user, Organization $organization, ?string $inviterName = null): bool
    {
        // Use Laravel's password reset system to generate a proper reset link
        $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);
        
        if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
            // If password reset email was sent, also send a custom invitation email
            $resetLink = url('/password/reset'); // Generic link, user will get reset email separately
            
            return $this->sendFromTemplate(
                'team_invitation',
                $user->email,
                [
                    'user_name' => $user->name,
                    'organization_name' => $organization->name,
                    'reset_link' => $resetLink,
                ],
                $organization,
                $user
            );
        }
        
        // Fallback: send invitation email without reset link if password reset fails
        return $this->sendFromTemplate(
            'team_invitation',
            $user->email,
            [
                'user_name' => $user->name,
                'organization_name' => $organization->name,
                'reset_link' => url('/password/reset'),
            ],
            $organization,
            $user
        );
    }

    /**
     * Send profile update notification email
     */
    public function sendProfileUpdateNotification(User $user, array $changes): bool
    {
        $organization = $user->organization;
        if (!$organization) {
            return false;
        }

        $changesList = [];
        foreach ($changes as $field => $value) {
            $changesList[] = "- {$field}: {$value}";
        }

        return $this->sendFromTemplate(
            'profile_updated',
            $user->email,
            [
                'user_name' => $user->name,
                'changes_list' => implode("\n", $changesList),
            ],
            $organization,
            $user
        );
    }

    /**
     * Send ticket response email
     */
    public function sendTicketResponse(\App\Models\SupportTicket $ticket, string $message, User $agent): bool
    {
        return $this->sendFromTemplate(
            'ticket_response',
            $ticket->user->email,
            [
                'user_name' => $ticket->user->name,
                'ticket_number' => $ticket->ticket_number,
                'ticket_subject' => $ticket->subject,
                'ticket_status' => ucfirst($ticket->status),
                'message' => $message,
                'agent_name' => $agent->name,
            ],
            $ticket->organization,
            $ticket->user
        );
    }

    /**
     * Send trial ending notification
     */
    public function sendTrialEndingNotification(Organization $organization, int $daysLeft): bool
    {
        $user = $organization->users()->first();

        if (!$user) {
            return false;
        }

        return $this->sendFromTemplate(
            'trial_ending',
            $user->email,
            [
                'user_name' => $user->name,
                'days_left' => $daysLeft,
            ],
            $organization,
            $user
        );
    }

    /**
     * Notify about account suspension
     */
    public function sendSuspensionNotification(Organization $organization, string $reason): bool
    {
        $user = $organization->users()->first();

        if (!$user) {
            return false;
        }

        return $this->sendFromTemplate(
            'account_suspended',
            $user->email,
            [
                'user_name' => $user->name,
                'suspension_reason' => $reason,
                'support_email' => config('mail.from.address'),
            ],
            $organization,
            $user
        );
    }
}

