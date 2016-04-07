<?php
namespace app\domain\chat;

class FriendsList
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $onlineUsers = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Sets who is online among this friends list.
     * @param array $onlineUsers Online Users IDs.
     */
    public function setOnline(array $onlineUsers = [])
    {
        $this->onlineUsers = $onlineUsers;

        foreach ($this->data as $projectIndex => $projects) {
            foreach ($projects['threads'] as $threadIndex => $thread) {
                if (in_array($thread['other_party']['user_id'], $this->onlineUsers)) {
                    $this->data[$projectIndex]['threads'][$threadIndex]['online'] = true;
                }
            }
        }
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * Ids of users in the friends list
     * @return array
     */
    public function getUserIds()
    {
        $userIds = [];

        foreach ($this->data as $project) {
            foreach ($project['threads'] as $thread) {
                array_push($userIds, $thread['other_party']['user_id']);
            }
        }

        return array_unique($userIds);
    }

    /**
     * Return JSON representation.
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->data);
    }

    /**
     * Return array representation.
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }
}
