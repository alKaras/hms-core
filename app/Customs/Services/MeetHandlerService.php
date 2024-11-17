<?php

namespace App\Customs\Services;

class MeetHandlerService
{
    //@TODO Implement while using google Calendar Api or Zoom APi or Google Meet REST API
    // public function createEvent()
    // {
    // }

    // public function deleteEvent()
    // {
    // }

    public function createLink(): string
    {
        //@TODO Implement creation of link while using Zoom API or Google Meet REST API

        return env('TEST_GOOGLE_MEET_PERMANENT_LINK');
    }


}