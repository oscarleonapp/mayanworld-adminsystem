<?php

namespace App\Models;

use App\Core\Model;

class MeetingPoint extends Model
{
    protected $table = 'meeting_points';

    /**
     * Get all active meeting points
     */
    public function getActive()
    {
        return $this->findAll(['is_active' => 1], "title ASC");
    }

    /**
     * Get meeting points associated with a tour via pivot
     */
    public function getByTourId($tourId)
    {
        $sql = "SELECT mp.*, pivot.display_order, pivot.tour_id, pivot.meeting_point_id
                FROM meeting_points mp
                JOIN tour_meeting_points_pivot pivot ON mp.id = pivot.meeting_point_id
                WHERE pivot.tour_id = :tour_id
                AND mp.is_active = 1
                ORDER BY pivot.display_order ASC, mp.title ASC";
        
        return $this->db->fetchAll($sql, ['tour_id' => $tourId]);
    }

    /**
     * Assign a meeting point to a tour (if not already assigned)
     */
    public function assignToTour($tourId, $meetingPointId)
    {
        // Check if already assigned
        $exists = $this->db->fetchOne(
            "SELECT 1 FROM tour_meeting_points_pivot WHERE tour_id = :tid AND meeting_point_id = :mpid",
            ['tid' => $tourId, 'mpid' => $meetingPointId]
        );

        if (!$exists) {
            $this->db->insert('tour_meeting_points_pivot', [
                'tour_id' => $tourId,
                'meeting_point_id' => $meetingPointId
            ]);
        }
        return true;
    }

    /**
     * Remove a meeting point from a tour
     */
    public function removeFromTour($tourId, $meetingPointId)
    {
        return $this->db->delete(
            'tour_meeting_points_pivot',
            "tour_id = :tid AND meeting_point_id = :mpid",
            ['tid' => $tourId, 'mpid' => $meetingPointId]
        );
    }
}
