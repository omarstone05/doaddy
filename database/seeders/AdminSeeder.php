<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminRole;
use App\Models\PlatformSetting;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin roles
        $superAdmin = AdminRole::firstOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Administrator',
                'permissions' => [
                    'view_dashboard',
                    'manage_organizations',
                    'manage_users',
                    'manage_settings',
                    'manage_tickets',
                    'manage_emails',
                    'manage_billing',
                    'view_logs',
                    'impersonate_users',
                ],
            ]
        );

        AdminRole::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrator',
                'permissions' => [
                    'view_dashboard',
                    'manage_organizations',
                    'manage_users',
                    'manage_tickets',
                    'view_logs',
                ],
            ]
        );

        AdminRole::firstOrCreate(
            ['slug' => 'support'],
            [
                'name' => 'Support Agent',
                'permissions' => [
                    'view_dashboard',
                    'manage_tickets',
                    'view_logs',
                ],
            ]
        );

        // Create default super admin user if doesn't exist
        // IMPORTANT: Change password immediately after first login in production!
        $defaultPassword = env('ADMIN_DEFAULT_PASSWORD', 'admin123');
        $admin = User::firstOrCreate(
            ['email' => 'admin@addybusiness.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($defaultPassword),
                'email_verified_at' => now(),
                'is_super_admin' => true,
            ]
        );

        if (!$admin->adminRoles()->where('slug', 'super_admin')->exists()) {
            $admin->adminRoles()->attach($superAdmin->id);
        }

        // Update platform settings with new fields
        $settings = [
            // AI Settings
            ['key' => 'ai_provider', 'value' => 'openai', 'type' => 'string', 'group' => 'ai', 'label' => 'AI Provider', 'description' => 'OpenAI or Anthropic'],
            ['key' => 'openai_api_key', 'value' => env('OPENAI_API_KEY'), 'type' => 'encrypted', 'group' => 'ai', 'label' => 'OpenAI API Key'],
            ['key' => 'openai_model', 'value' => 'gpt-4o', 'type' => 'string', 'group' => 'ai', 'label' => 'OpenAI Model'],
            ['key' => 'anthropic_api_key', 'value' => env('ANTHROPIC_API_KEY'), 'type' => 'encrypted', 'group' => 'ai', 'label' => 'Anthropic API Key'],
            ['key' => 'anthropic_model', 'value' => 'claude-sonnet-4-20250514', 'type' => 'string', 'group' => 'ai', 'label' => 'Anthropic Model'],
            
            // Email Settings
            ['key' => 'mail_from_address', 'value' => 'noreply@addybusiness.com', 'type' => 'string', 'group' => 'email', 'label' => 'From Email'],
            ['key' => 'mail_from_name', 'value' => 'Addy Business', 'type' => 'string', 'group' => 'email', 'label' => 'From Name'],
            ['key' => 'support_email', 'value' => 'support@addybusiness.com', 'type' => 'string', 'group' => 'email', 'label' => 'Support Email'],
            
            // Feature Flags
            ['key' => 'enable_signups', 'value' => 'true', 'type' => 'boolean', 'group' => 'features', 'label' => 'Enable Signups'],
            ['key' => 'enable_addy_ai', 'value' => 'true', 'type' => 'boolean', 'group' => 'features', 'label' => 'Enable Addy AI', 'is_public' => true],
            ['key' => 'enable_predictions', 'value' => 'true', 'type' => 'boolean', 'group' => 'features', 'label' => 'Enable Predictions', 'is_public' => true],
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group' => 'general', 'label' => 'Maintenance Mode'],
            
            // Billing
            ['key' => 'stripe_key', 'value' => env('STRIPE_KEY'), 'type' => 'encrypted', 'group' => 'billing', 'label' => 'Stripe Secret Key'],
            ['key' => 'stripe_webhook_secret', 'value' => env('STRIPE_WEBHOOK_SECRET'), 'type' => 'encrypted', 'group' => 'billing', 'label' => 'Stripe Webhook Secret'],
            ['key' => 'trial_days', 'value' => '14', 'type' => 'integer', 'group' => 'billing', 'label' => 'Trial Period (Days)'],
            
            // System
            ['key' => 'max_organizations_per_user', 'value' => '3', 'type' => 'integer', 'group' => 'general', 'label' => 'Max Organizations Per User'],
            ['key' => 'session_timeout', 'value' => '120', 'type' => 'integer', 'group' => 'general', 'label' => 'Session Timeout (Minutes)'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Email templates
        $templates = [
            [
                'name' => 'Welcome Email',
                'slug' => 'welcome',
                'subject' => 'Welcome to Addy Business!',
                'category' => 'onboarding',
                'body' => 'Hi {{user_name}},

Welcome to Addy Business! We\'re excited to have you on board.

Your organization "{{organization_name}}" has been created successfully.

Get started:
- Set up your first account
- Create your first transaction
- Chat with Addy, your AI business assistant

Questions? Reply to this email or visit our support center.

Best regards,
The Addy Business Team',
                'variables' => ['user_name', 'organization_name'],
            ],
            [
                'name' => 'Ticket Response',
                'slug' => 'ticket_response',
                'subject' => 'Re: {{ticket_subject}} [#{{ticket_number}}]',
                'category' => 'support',
                'body' => 'Hi {{user_name}},

{{message}}

Ticket: #{{ticket_number}}
Status: {{ticket_status}}

Reply to this email to continue the conversation.

Best regards,
{{agent_name}}
Addy Business Support',
                'variables' => ['user_name', 'ticket_number', 'ticket_subject', 'ticket_status', 'message', 'agent_name'],
            ],
            [
                'name' => 'Trial Ending Soon',
                'slug' => 'trial_ending',
                'subject' => 'Your trial ends in {{days_left}} days',
                'category' => 'billing',
                'body' => 'Hi {{user_name}},

Your Addy Business trial will end in {{days_left}} days.

To continue using Addy:
1. Go to Settings > Billing
2. Choose a plan
3. Enter your payment details

Questions? We\'re here to help!

Best regards,
The Addy Business Team',
                'variables' => ['user_name', 'days_left'],
            ],
            [
                'name' => 'Account Suspended',
                'slug' => 'account_suspended',
                'subject' => 'Your Addy Business account has been suspended',
                'category' => 'admin',
                'body' => 'Hi {{user_name}},

Your Addy Business account has been suspended.

Reason: {{suspension_reason}}

To reactivate your account, please contact support at {{support_email}}.

Best regards,
The Addy Business Team',
                'variables' => ['user_name', 'suspension_reason', 'support_email'],
            ],
            [
                'name' => 'Payment Confirmation',
                'slug' => 'payment_confirmation',
                'subject' => 'Payment Confirmation - {{payment_reference}}',
                'category' => 'billing',
                'body' => 'Hi {{user_name}},

Thank you for your payment!

Payment Details:
- Amount: {{payment_amount}}
- Reference: {{payment_reference}}
- Date: {{payment_date}}
- Method: {{payment_method}}

Your payment has been successfully processed.

Best regards,
Addy Business Team',
                'variables' => ['user_name', 'organization_name', 'payment_amount', 'payment_reference', 'payment_date', 'payment_method'],
            ],
            [
                'name' => 'Subscription Renewal',
                'slug' => 'subscription_renewal',
                'subject' => 'Subscription Renewed - {{organization_name}}',
                'category' => 'billing',
                'body' => 'Hi {{user_name}},

Your subscription has been renewed successfully!

Plan: {{plan_name}}
Amount: {{amount}}
Billing Period: {{billing_period}}
Next Renewal: {{renewal_date}}

Thank you for your continued support!

Best regards,
Addy Business Team',
                'variables' => ['user_name', 'organization_name', 'plan_name', 'amount', 'billing_period', 'renewal_date'],
            ],
            [
                'name' => 'Invoice Generated',
                'slug' => 'invoice_generated',
                'subject' => 'New Invoice - {{invoice_number}}',
                'category' => 'billing',
                'body' => 'Hi {{user_name}},

A new invoice has been generated for {{organization_name}}.

Invoice Number: {{invoice_number}}
Amount: {{invoice_amount}}
Due Date: {{due_date}}

You can view and pay your invoice by logging into your dashboard.

Best regards,
Addy Business Team',
                'variables' => ['user_name', 'organization_name', 'invoice_number', 'invoice_amount', 'due_date'],
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}

