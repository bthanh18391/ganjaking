<?php
/**
 * Join table in get task query
 *
 * @param  string $join
 *
 * @return string
 */
function pm_pro_task_join( $join ) {
    global $wpdb;

    $tb_tasks = pm_tb_prefix() . 'pm_tasks';
    $tb_rp    = pm_tb_prefix() . 'pm_role_project';
    $tb_rpu   = pm_tb_prefix() . 'pm_role_project_users';
    $tb_rpc   = pm_tb_prefix() . 'pm_role_project_capabilities';

    $join .= " LEFT JOIN $tb_rp as rp ON rp.project_id = $tb_tasks.project_id";
    $join .= " LEFT JOIN $tb_rpu as rpu ON rpu.role_project_id = rp.id";
    $join .= " LEFT JOIN $tb_rpc as rpc ON rpc.role_project_id = rp.id";
    $join .= " LEFT JOIN $tb_rpc as rpcl ON rpcl.role_project_id = rp.id";

    return $join;
}

/**
 * Set where condition for get tasks query
 *
 * @param  string $where
 * @param  int $user_id
 *
 * @return string
 */
function pm_pro_task_where( $where, $user_id ) {
    $tb_tasks = pm_tb_prefix() . 'pm_tasks';

    $where .= " AND (
        (
            $tb_tasks.is_private = 1
            AND rpu.user_id = $user_id
            AND rpc.capability_id = 8
            AND (
                ( list.is_private = 1 AND rpcl.capability_id = 4 )
                OR
                list.is_private = 0
                OR
                list.created_by = $user_id
            )
        )
        OR
        $tb_tasks.is_private = 0
        OR
        $tb_tasks.created_by = $user_id
    )";

    return $where;
}
