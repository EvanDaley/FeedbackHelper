<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External Web Service
 *
 * @package    localfeedbackhelper
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/local/webhooks/locallib.php");
require_once(__DIR__ . '/../../config.php');

class local_feedbackhelper_external extends external_api
{

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_parameters()
    {
        return new external_function_parameters(
            array('criteria' => new external_value(PARAM_RAW, 'ids to determine which record to delete"', VALUE_DEFAULT, '{}! '))
        );
    }

    /**
     * Returns welcome message
     * @param string $queryObject
     * @return string welcome message
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function delete($criteria)
    {
        global $DB;

        $response = [
            'status' => 200
        ];

        try {
            $criteria = json_decode($criteria, true);

            if (!isset($criteria["user_ids"])) {
                return json_encode([
                    'message' => "'user_ids' is required.",
                    'input' => $criteria
                ]);
            }

            if (!isset($criteria["feedback_ids"])) {
                return json_encode([
                    'message' => "'feedback_ids' is required.",
                    'input' => $criteria
                ]);
            }

            $userIds = implode(', ', $criteria['user_ids']);
            $feedbackIds = implode(', ', $criteria['feedback_ids']);
            $deleteFrom = 'feedback_completed';

            $selectStatement = "feedback in ({$feedbackIds}) and userid in ({$userIds})";

            $response['delete_where'] = $selectStatement;

            $records = $DB->get_records_select($deleteFrom, $selectStatement);

            $i = 0;
            foreach ($records as $key => $value) {
                $i++;
                $response['deleting_records'][$key] = $value;
            }

             $DB->delete_records_select($deleteFrom, $selectStatement);

            if ($i == 0) {
                $response['message'] = "No matching records found.";
            } else {
                $response['message'] = "Successfully deleted record(s).";
            }
        } catch (\Throwable $e) {
            $response['status'] = 500;
            $response['message'] = $e->getMessage();
        }

        return json_encode($response);
    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function delete_returns()
    {
        return new external_value(PARAM_RAW, 'JSON Response');
    }
}
