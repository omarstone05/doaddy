<?php

namespace App\Traits;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

trait CreatesNotifications
{
    /**
     * Create a success notification for the authenticated user
     */
    protected function notifySuccess(string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->createNotification('success', $title, $message, $actionUrl);
    }

    /**
     * Create an error notification for the authenticated user
     */
    protected function notifyError(string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->createNotification('error', $title, $message, $actionUrl);
    }

    /**
     * Create an info notification for the authenticated user
     */
    protected function notifyInfo(string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->createNotification('info', $title, $message, $actionUrl);
    }

    /**
     * Create a warning notification for the authenticated user
     */
    protected function notifyWarning(string $title, string $message, ?string $actionUrl = null): Notification
    {
        return $this->createNotification('warning', $title, $message, $actionUrl);
    }

    /**
     * Create a notification and return a redirect response with flash message
     */
    protected function notifyAndRedirect(string $type, string $title, string $message, $redirect, ?string $actionUrl = null)
    {
        $this->createNotification($type, $title, $message, $actionUrl);
        
        $flashKey = $type === 'error' ? 'error' : 'message';
        $flashMessage = $message;
        
        if (is_string($redirect)) {
            return redirect($redirect)->with($flashKey, $flashMessage);
        }
        
        return $redirect->with($flashKey, $flashMessage);
    }

    /**
     * Create a notification and return back with flash message
     */
    protected function notifyAndBack(string $type, string $title, string $message, ?string $actionUrl = null)
    {
        $this->createNotification($type, $title, $message, $actionUrl);
        
        $flashKey = $type === 'error' ? 'error' : 'message';
        
        return back()->with($flashKey, $message);
    }

    /**
     * Create a notification for the authenticated user
     */
    protected function createNotification(string $type, string $title, string $message, ?string $actionUrl = null): Notification
    {
        if (!Auth::check()) {
            throw new \RuntimeException('Cannot create notification: user not authenticated');
        }

        $user = Auth::user();
        
        return Notification::createForUser(
            $user->id,
            $user->organization_id,
            $type,
            $title,
            $message,
            $actionUrl
        );
    }
}

