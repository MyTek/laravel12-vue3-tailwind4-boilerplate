<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('person.{personId}', function ($user, $personId) {
    return true;
});
