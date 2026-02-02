<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Organization $organization;
    public OrganizationRole $role;
    public User $invitedBy;
    public string $inviteToken;
    public ?string $inviteeName;
    public string $acceptUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Organization $organization,
        OrganizationRole $role,
        User $invitedBy,
        string $inviteToken,
        ?string $inviteeName = null
    ) {
        $this->organization = $organization;
        $this->role = $role;
        $this->invitedBy = $invitedBy;
        $this->inviteToken = $inviteToken;
        $this->inviteeName = $inviteeName;
        $this->acceptUrl = config('app.url') . '/invitations/accept/' . $inviteToken;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to join {$this->organization->name} on Addy",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.organization-invitation',
            with: [
                'organizationName' => $this->organization->name,
                'organizationLogo' => $this->organization->logo_url,
                'roleName' => $this->role->name,
                'roleDescription' => $this->role->description,
                'invitedByName' => $this->invitedBy->name,
                'inviteeName' => $this->inviteeName,
                'acceptUrl' => $this->acceptUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
