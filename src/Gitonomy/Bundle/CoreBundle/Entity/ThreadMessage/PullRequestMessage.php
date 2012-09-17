<?php

namespace Gitonomy\Bundle\CoreBundle\Entity\ThreadMessage;

use Gitonomy\Bundle\CoreBundle\Entity\ThreadMessage;

class PullRequestMessage extends ThreadMessage
{
    protected $message;

    public function getSentence()
    {
        return 'asked a pull request';
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    public function getName()
    {
        return 'pr';
    }
}
