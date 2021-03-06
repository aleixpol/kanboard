<?php

namespace Model;

use PDO;

/**
 * Task Export model
 *
 * @package  model
 * @author   Frederic Guillot
 */
class TaskExport extends Base
{
    /**
     * Fetch tasks and return the prepared CSV
     *
     * @access public
     * @param  integer    $project_id      Project id
     * @param  mixed      $from            Start date (timestamp or user formatted date)
     * @param  mixed      $to              End date (timestamp or user formatted date)
     * @return array
     */
    public function export($project_id, $from, $to)
    {
        $tasks = $this->getTasks($project_id, $from, $to);
        $results = array($this->getColumns());

        foreach ($tasks as &$task) {
            $results[] = array_values($this->formatOutput($task));
        }

        return $results;
    }

    /**
     * Get the list of tasks for a given project and date range
     *
     * @access public
     * @param  integer    $project_id      Project id
     * @param  mixed      $from            Start date (timestamp or user formatted date)
     * @param  mixed      $to              End date (timestamp or user formatted date)
     * @return array
     */
    public function getTasks($project_id, $from, $to)
    {
        $sql = '
            SELECT
            tasks.id,
            projects.name AS project_name,
            tasks.is_active,
            project_has_categories.name AS category_name,
            columns.title AS column_title,
            tasks.position,
            tasks.color_id,
            tasks.date_due,
            creators.username AS creator_username,
            users.username AS assignee_username,
            tasks.score,
            tasks.title,
            tasks.date_creation,
            tasks.date_modification,
            tasks.date_completed
            FROM tasks
            LEFT JOIN users ON users.id = tasks.owner_id
            LEFT JOIN users AS creators ON creators.id = tasks.creator_id
            LEFT JOIN project_has_categories ON project_has_categories.id = tasks.category_id
            LEFT JOIN columns ON columns.id = tasks.column_id
            LEFT JOIN projects ON projects.id = tasks.project_id
            WHERE tasks.date_creation >= ? AND tasks.date_creation <= ? AND tasks.project_id = ?
        ';

        if (! is_numeric($from)) {
            $from = $this->dateParser->resetDateToMidnight($this->dateParser->getTimestamp($from));
        }

        if (! is_numeric($to)) {
            $to = $this->dateParser->resetDateToMidnight(strtotime('+1 day', $this->dateParser->getTimestamp($to)));
        }

        $rq = $this->db->execute($sql, array($from, $to, $project_id));
        return $rq->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Format the output of a task array
     *
     * @access public
     * @param  array     $task    Task properties
     * @return array
     */
    public function formatOutput(array &$task)
    {
        $colors = $this->color->getList();
        $task['score'] = $task['score'] ?: '';
        $task['is_active'] = $task['is_active'] == Task::STATUS_OPEN ? e('Open') : e('Closed');
        $task['color_id'] = $colors[$task['color_id']];
        $task['date_creation'] = date('Y-m-d', $task['date_creation']);
        $task['date_due'] = $task['date_due'] ? date('Y-m-d', $task['date_due']) : '';
        $task['date_modification'] = $task['date_modification'] ? date('Y-m-d', $task['date_modification']) : '';
        $task['date_completed'] = $task['date_completed'] ? date('Y-m-d', $task['date_completed']) : '';

        return $task;
    }

    /**
     * Get column titles
     *
     * @access public
     * @return array
     */
    public function getColumns()
    {
        return array(
            e('Task Id'),
            e('Project'),
            e('Status'),
            e('Category'),
            e('Column'),
            e('Position'),
            e('Color'),
            e('Due date'),
            e('Creator'),
            e('Assignee'),
            e('Complexity'),
            e('Title'),
            e('Creation date'),
            e('Modification date'),
            e('Completion date'),
        );
    }
}
