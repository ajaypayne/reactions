<?php

/*
 * This file is part of fof/reactions.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\Reactions\Listener;

use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Post;
use Flarum\User\User;
use FoF\Reactions\Event\PostWasReacted;
use FoF\Reactions\Event\PostWasUnreacted;
use FoF\Reactions\Notification\PostReactedBlueprint;
use Illuminate\Contracts\Events\Dispatcher;

class SendNotificationWhenPostIsReacted
{
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @param NotificationSyncer $notifications
     */
    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureNotificationTypes::class, [$this, 'registerNotificationType']);
        $events->listen(PostWasReacted::class, [$this, 'whenPostWasReacted']);
        $events->listen(PostWasUnreacted::class, [$this, 'whenPostWasUnreacted']);
    }

    /**
     * @param ConfigureNotificationTypes $event
     */
    public function registerNotificationType(ConfigureNotificationTypes $event)
    {
        $event->add(PostReactedBlueprint::class, BasicPostSerializer::class, ['alert']);
    }

    /**
     * @param PostWasReacted $event
     */
    public function whenPostWasReacted(PostWasReacted $event)
    {
        $this->sync($event->post, $event->user, $event->reaction, [$event->post->user]);
    }

    /**
     * @param Post  $post
     * @param User  $user
     * @param array $recipients
     */
    public function sync(Post $post, User $user, $reaction, array $recipients)
    {
        if ($post->user->id != $user->id) {
            $this->notifications->sync(
                new PostReactedBlueprint($post, $user, $reaction),
                $recipients
            );
        }
    }

    /**
     * @param PostWasUnreacted $event
     */
    public function whenPostWasUnreacted(PostWasUnreacted $event)
    {
        $this->sync($event->post, $event->user, '', []);
    }
}
