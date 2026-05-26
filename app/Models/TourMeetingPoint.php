<?php

namespace App\Models;

use App\Core\Model;

class TourMeetingPoint extends Model
{
    protected $table = 'tour_meeting_points';
    protected $fillable = [
        'tour_id',
        'type',
        'title',
        'address',
        'map_link',
        'description'
    ];

    public function getByTourId($tourId)
    {
        return $this->findAll(['tour_id' => $tourId]);
    }
}
