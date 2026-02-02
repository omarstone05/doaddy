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

class AddedToOrganizationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Organization $organization;
    public OrganizationRole $role;
    public User $addedBy;
    public string $dashboardUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Organization $organization,
        OrganizationRole $role,
        User $addedBy
    ) {
        $this->organization = $organization;
        $this->role = $role;
        $this->addedBy = $addedBy;
        $this->dashboardUrl = config('app.url') . '/dashboard';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been added to {$this->organization->name} on Addy",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.added-to-organization',
            with: [
                'organizationName' => $this->organization->name,
                'roleName' => $this->role->name,
                'roleDescription' => $this->role->description,
                'addedByName' => $this->addedBy->name,
                'dashboardUrl' => $this->dashboardUrl,
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
