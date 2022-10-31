<?php

/**
 * Plugin Name: Custom API
 * Description: Custom API for Wild Country interview!
 * Version: 1.0
 * Author: Yash Gada
 */

function get_all_students()
{
    global $wpdb;
    $wpdb->show_errors();
    $results = $wpdb->get_results('select * from wc_students');
    return rest_ensure_response($results);
};
function get_single_student($request)
{
    $s_id = (int) $request['s_id'];
    global $wpdb;
    $wpdb->show_errors();
    $results_query = $wpdb->prepare("select * from wc_students where s_id= %d ;", $s_id);
    $results = $wpdb->get_results($results_query);
    if (count($results) < 1) {
        return new WP_Error('No Student found', 'Invalid Student ID', array('status' => 404));
    }
    return new WP_REST_Response($results[0], 200);
};
function get_student_hobbies($request)
{
    $s_id = (int) $request['s_id'];
    global $wpdb;
    $wpdb->show_errors();
    $results_query = $wpdb->prepare("select h_id,hobby_name from wc_hobbies where s_id=%d;", $s_id);
    $results = $wpdb->get_results($results_query);
    if (count($results) < 1) {
        return new WP_Error('No Student found', 'Either student ID is wrong, or the Student has no hobbies', array('status' => 404));
    }
    return new WP_REST_Response($results, 200);
};
function add_hobby_to_student($request)
{
    $params = $request->get_json_params();
    $hobby = $params['hobby'];
    $s_id = (int) $request['s_id'];
    global $wpdb;
    $wpdb->show_errors();
    $results_query = $wpdb->prepare("INSERT into wc_hobbies (s_id,hobby_name) values (%d,%s);", $s_id, $hobby);
    $wpdb->get_results($results_query);

    // todo check and avoid student's hobby clashes

    return new WP_REST_Response(["rows_affected" => 1], 201);
};
function edit_hobby_name($request)
{
    global $wpdb;
    $wpdb->show_errors();
    $params = $request->get_json_params();
    $hobby = $params['hobby_name'];
    $h_id = (int) $request['h_id'];

    // todo prevent student's hobby clash

    $result = $wpdb->update('wc_hobbies', ['hobby_name' => $hobby], ['h_id' => $h_id], ['%s'], ['%d']);
    if (!$result) {
        return new WP_Error(
            'No Such Hobby found',
            'The hobby ID is wrong, please check and try again',
            array('status' => 404)
        );
    }
    return rest_ensure_response(['rows_affected' => $result]);
}


add_action('rest_api_init', function () {
    register_rest_route('wc/v1', 'students', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'get_all_students'
    ]);
    register_rest_route('wc/v1', 'students/(?P<s_id>[\d]+)', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'get_single_student'
    ]);
    register_rest_route('wc/v1', 'hobbies/(?P<s_id>[\d]+)', [
        [
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'get_student_hobbies'
        ],
        [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'add_hobby_to_student'
        ]
    ]);
    register_rest_route('wc/v1', 'hobbies/(?P<h_id>[\d]+)', [
        'methods' => 'PUT',
        'callback' => 'edit_hobby_name'
    ]);
});
