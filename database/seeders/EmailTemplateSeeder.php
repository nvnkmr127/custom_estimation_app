<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public function run()
    {
        $templates = [
            [
                'code' => 'EST_CREATED',
                'event_trigger' => 'estimate.created',
                'name' => 'Internal: New Estimate Drafted',
                'subject' => 'New Estimate Created: {{ estimate_number }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; rounded: 12px;">
                        <h2 style="color: #4f46e5;">New Estimate Created</h2>
                        <p>Hello,</p>
                        <p>A new estimate has been drafted in the system.</p>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <p><strong>Estimate #:</strong> {{ estimate_number }}</p>
                            <p><strong>Client:</strong> {{ client_name }}</p>
                            <p><strong>Amount:</strong> {{ total_amount }}</p>
                            <p><strong>Created By:</strong> {{ creator_name }}</p>
                        </div>
                        <p>You can view the details here:</p>
                        <a href="{{ view_url }}" style="display: inline-block; background: #4f46e5; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">View Estimate</a>
                    </div>
                ',
                'description' => 'Notifies internal staff when a new estimate is first created as a draft.',
            ],
            [
                'code' => 'EST_SUBMITTED',
                'event_trigger' => 'estimate.submitted',
                'name' => 'Internal: Estimate Submitted for Approval',
                'subject' => 'Approval Required: {{ estimate_number }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                         <h2 style="color: #4f46e5;">Approval Requested</h2>
                         <p>The following estimate has been submitted and is now awaiting approval:</p>
                         <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <p><strong>Estimate #:</strong> {{ estimate_number }}</p>
                            <p><strong>Client:</strong> {{ client_name }}</p>
                            <p><strong>Total Amount:</strong> {{ total_amount }}</p>
                         </div>
                         <p>Please review and take action as soon as possible.</p>
                         <a href="{{ view_url }}" style="display: inline-block; background: #4f46e5; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">Review Now</a>
                    </div>
                ',
                'description' => 'Sent to managers when an estimate is submitted into the approval workflow.',
            ],
            [
                'code' => 'EST_SENT',
                'event_trigger' => 'estimate.sent',
                'name' => 'Client: Estimate Sent',
                'subject' => 'Your Estimate {{ estimate_number }} from Our Company',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <p>Dear {{ notifiable_name }},</p>
                        <p>We are pleased to provide you with the estimate you requested. Please find the details below:</p>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <p><strong>Estimate Reference:</strong> {{ estimate_number }}</p>
                            <p><strong>Total Amount:</strong> {{ total_amount }}</p>
                        </div>
                        <p>You can view the full estimate and accept or decline it through our secure portal by clicking the button below:</p>
                        <a href="{{ view_url }}" style="display: inline-block; background: #4f46e5; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">View & Respond to Estimate</a>
                        <p style="margin-top: 20px;">If you have any questions, please feel free to contact us.</p>
                        <p>Best regards,<br>The Team</p>
                    </div>
                ',
                'description' => 'The primary email sent to clients including the link to view their estimate.',
            ],
            [
                'code' => 'EST_APPROVED',
                'event_trigger' => 'estimate.approved',
                'name' => 'Internal: Estimate Fully Approved',
                'subject' => 'Estimate Approved: {{ estimate_number }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #059669;">Great News! Estimate Approved</h2>
                        <p>Estimate {{ estimate_number }} for {{ client_name }} has successfully cleared the internal approval workflow.</p>
                        <p><strong>Total:</strong> {{ total_amount }}</p>
                        <p>This estimate is now ready to be sent to the client.</p>
                        <a href="{{ view_url }}" style="display: inline-block; background: #059669; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">Go to Estimate</a>
                    </div>
                ',
                'description' => 'Notifies the creator when their estimate has finished the internal approval process.',
            ],
            [
                'code' => 'EST_ACCEPTED',
                'event_trigger' => 'estimate.accepted',
                'name' => 'Internal: Client Accepted Estimate',
                'subject' => 'ACCEPTED: {{ estimate_number }} by {{ client_name }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #059669;">Success! Client Accepted</h2>
                        <p>Good news! The client <strong>{{ client_name }}</strong> has accepted estimate <strong>{{ estimate_number }}</strong>.</p>
                        <p><strong>Value:</strong> {{ total_amount }}</p>
                        <p>You can now proceed with the next steps of the project.</p>
                        <a href="{{ view_url }}" style="display: inline-block; background: #059669; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">View details</a>
                    </div>
                ',
                'description' => 'Notifies the team when a client accepts an estimate on the portal.',
            ],
            [
                'code' => 'EST_DECLINED',
                'event_trigger' => 'estimate.declined',
                'name' => 'Internal: Client Declined Estimate',
                'subject' => 'DECLINED: {{ estimate_number }} by {{ client_name }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #dc2626;">Notice: Client Declined</h2>
                        <p>The client <strong>{{ client_name }}</strong> has declined estimate <strong>{{ estimate_number }}</strong>.</p>
                        <p>Please check the system for any feedback or decline reasons provided by the client.</p>
                        <a href="{{ view_url }}" style="display: inline-block; background: #dc2626; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">Check feedback</a>
                    </div>
                ',
                'description' => 'Notifies the team when a client declines an estimate.',
            ],
            [
                'code' => 'APP_REQUESTED',
                'event_trigger' => 'approval.requested',
                'name' => 'Approval Task Assigned',
                'subject' => 'Action Required: Approval Task for {{ estimate_estimate_number }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #4f46e5;">Approval Task</h2>
                        <p>Hello {{ approver_name }},</p>
                        <p>An estimate requires your review and approval as part of the internal workflow.</p>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <p><strong>Estimate:</strong> {{ estimate_estimate_number }}</p>
                            <p><strong>Client:</strong> {{ client_name }}</p>
                            <p><strong>Amount:</strong> {{ total_amount }}</p>
                            <p><strong>Submitted By:</strong> {{ estimate_created_by_name }}</p>
                        </div>
                        <a href="{{ estimate_url }}" style="display: inline-block; background: #4f46e5; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">Go to Approval Desk</a>
                    </div>
                ',
                'description' => 'Sent directly to an individual approver when or when it is their turn in the chain.',
            ],
            [
                'code' => 'APP_REJECTED',
                'event_trigger' => 'approval.rejected',
                'name' => 'Internal: Step Rejected',
                'subject' => 'REJECTED: {{ estimate_estimate_number }} in Approval Flow',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #dc2626;">Approval Rejected</h2>
                        <p>Estimate <strong>{{ estimate_estimate_number }}</strong> has been rejected by <strong>{{ approver_name }}</strong>.</p>
                        <p>You may need to review the comments, make changes, and resubmit if necessary.</p>
                        <a href="{{ estimate_url }}" style="display: inline-block; background: #dc2626; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">View comments</a>
                    </div>
                ',
                'description' => 'Notifies the estimate creator that an approver has rejected a step.',
            ],
            [
                'code' => 'EST_UPDATED',
                'event_trigger' => 'estimate.updated',
                'name' => 'Internal: Estimate Updated',
                'subject' => 'Updated: {{ estimate_number }} - {{ client_name }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #4f46e5;">Estimate Updated</h2>
                        <p>Estimate <strong>{{ estimate_number }}</strong> has been modified.</p>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <p><strong>Client:</strong> {{ client_name }}</p>
                            <p><strong>New Total:</strong> {{ total_amount }}</p>
                        </div>
                        <p>Check the version history for details on what changed.</p>
                        <a href="{{ view_url }}" style="display: inline-block; background: #4f46e5; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">View Changes</a>
                    </div>
                ',
                'description' => 'Sent when an existing estimate is saved with new changes.',
            ],
            [
                'code' => 'EST_VIEWED',
                'event_trigger' => 'estimate.viewed',
                'name' => 'Internal: Client Viewed Estimate',
                'subject' => 'VIEWED: {{ estimate_number }} by {{ client_name }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #4f46e5;">Interaction Alert</h2>
                        <p>Your client <strong>{{ client_name }}</strong> just viewed estimate <strong>{{ estimate_number }}</strong> on the portal.</p>
                        <p>This is a great time to follow up if they have any questions!</p>
                        <a href="{{ view_url }}" style="display: inline-block; background: #4f46e5; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">View Estimate Details</a>
                    </div>
                ',
                'description' => 'Real-time sales alert sent when a client opens their estimate link.',
            ],
            [
                'code' => 'EST_EXPIRED',
                'event_trigger' => 'estimate.expired',
                'name' => 'Internal: Estimate Expired',
                'subject' => 'EXPIRED: {{ estimate_number }} for {{ client_name }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #64748b;">Estimate Expired</h2>
                        <p>Estimate <strong>{{ estimate_number }}</strong> has reached its expiration date and is no longer active.</p>
                        <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <p><strong>Client:</strong> {{ client_name }}</p>
                            <p><strong>Expired Amount:</strong> {{ total_amount }}</p>
                        </div>
                        <p>You can duplicate this estimate to create a new version if the client is still interested.</p>
                        <a href="{{ view_url }}" style="display: inline-block; background: #64748b; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">View Expired Estimate</a>
                    </div>
                ',
                'description' => 'Sent when an estimate passes its validity date without being accepted.',
            ],
            [
                'code' => 'COMMENT_ADDED',
                'event_trigger' => 'comment.added',
                'name' => 'New Comment Alert',
                'subject' => 'New Comment on {{ estimate_reference }}: {{ commenter_name }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #4f46e5;">New Comment</h2>
                        <p><strong>{{ commenter_name }}</strong> added a comment to estimate <strong>{{ estimate_reference }}</strong>:</p>
                        <div style="background: #fefce8; border-left: 4px solid #eab308; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                            <p style="margin: 0; font-style: italic;">"{{ comment }}"</p>
                        </div>
                        <a href="{{ estimate_url }}" style="display: inline-block; background: #4f46e5; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">Reply to Comment</a>
                    </div>
                ',
                'description' => 'Sent to followers when a new internal or external comment is posted.',
            ],
            [
                'code' => 'CLIENT_CREATED',
                'event_trigger' => 'client.created',
                'name' => 'Internal: New Client Registered',
                'subject' => 'New Client Profile: {{ name }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #4f46e5;">New Client Profile</h2>
                        <p>A new client has been added to the system.</p>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <p><strong>Name:</strong> {{ name }}</p>
                            <p><strong>Email:</strong> {{ email }}</p>
                            <p><strong>Phone:</strong> {{ phone }}</p>
                        </div>
                        <p>You can now create estimates for this client.</p>
                    </div>
                ',
                'description' => 'Notifies staff when a new client record is created.',
            ],
            [
                'code' => 'USER_REG',
                'event_trigger' => 'user.registered',
                'name' => 'Internal: New Staff Joined',
                'subject' => 'Welcome New User: {{ name }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #4f46e5;">New User Registered</h2>
                        <p>A new staff account has been created.</p>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <p><strong>Name:</strong> {{ name }}</p>
                            <p><strong>Email:</strong> {{ email }}</p>
                        </div>
                        <p>Please ensure their permissions are correctly configured.</p>
                    </div>
                ',
                'description' => 'Alerts admins when a new user account is created in the system.',
            ],
            [
                'code' => 'APP_STEP_APPROVED',
                'event_trigger' => 'approval.approved',
                'name' => 'Internal: Approval Step Cleared',
                'subject' => 'STEP APPROVED: {{ estimate_estimate_number }} by {{ approver_name }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #059669;">Step Cleared</h2>
                        <p>One of the approval steps for estimate <strong>{{ estimate_estimate_number }}</strong> was approved by <strong>{{ approver_name }}</strong>.</p>
                        <p>The estimate is now moving to the next stage of the workflow.</p>
                        <a href="{{ estimate_url }}" style="display: inline-block; background: #059669; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">Track Progress</a>
                    </div>
                ',
                'description' => 'Sent to the author when an individual step in the chain is successfully approved.',
            ],
            [
                'code' => 'APP_CHANGES',
                'event_trigger' => 'approval.change_requested',
                'name' => 'Internal: Changes Requested',
                'subject' => 'REVISION NEEDED: {{ estimate_estimate_number }}',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #ea580c;">Changes Requested</h2>
                        <p><strong>{{ approver_name }}</strong> has requested changes to estimate <strong>{{ estimate_estimate_number }}</strong>.</p>
                        <p>Please review the feedback and update the estimate accordingly.</p>
                        <a href="{{ estimate_url }}" style="display: inline-block; background: #ea580c; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold;">View Feedback</a>
                    </div>
                ',
                'description' => 'Sent to the author when an approver asks for modifications instead of rejecting.',
            ],
            [
                'code' => 'USER_LOGIN',
                'event_trigger' => 'user.logged_in',
                'name' => 'Security: New Login detected',
                'subject' => 'New login to your account ({{ name }})',
                'body_html' => '
                    <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2 style="color: #4f46e5;">Login Alert</h2>
                        <p>Hello {{ name }},</p>
                        <p>This is a security notification that your account was recently accessed.</p>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <p><strong>Email:</strong> {{ email }}</p>
                            <p><strong>Time:</strong> {{ created_at }}</p>
                        </div>
                        <p>If this was not you, please contact support immediately.</p>
                    </div>
                ',
                'description' => 'Security notification sent when a user logs into the system.',
            ]
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['code' => $template['code']],
                $template
            );
        }
    }
}
