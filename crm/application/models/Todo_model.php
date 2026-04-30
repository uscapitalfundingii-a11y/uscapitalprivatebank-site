<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Todo_model extends App_Model
{
    public $todo_limit;

    public function __construct()
    {
        parent::__construct();
        $this->todo_limit = hooks()->apply_filters('todos_limit', 10);
        $this->load->model('misc_model');
    }

    public function setTodosLimit($limit)
    {
        $this->todo_limit = $limit;
    }

    public function getTodosLimit()
    {
        return $this->todo_limit;
    }

    public function get($id = '')
    {
        $this->db->where('staffid', get_staff_user_id());

        if (is_numeric($id)) {
            $this->db->where('todoid', $id);
            $todo = $this->db->get(db_prefix().'todos')->row();
            if ($todo) {
                $todo->attachments = $this->get_todo_attachments($id);
            }

            return $todo;
        }

        return $this->db->get(db_prefix().'todos')->result_array();
    }

    /**
     * Get all user todos
     * @param  boolean $finished is finished todos or not
     * @param  mixed $page     pagination limit page
     * @return array
     */
    public function get_todo_items($finished, $page = '')
    {
        $this->db->select();
        $this->db->from(db_prefix().'todos');
        $this->db->where('finished', $finished);
        $this->db->where('staffid', get_staff_user_id());
        $this->db->order_by('item_order', 'asc');
        if ($page != '' && $this->input->post('todo_page')) {
            $position = ($page * $this->todo_limit);
            $this->db->limit($this->todo_limit, $position);
        } else {
            $this->db->limit($this->todo_limit);
        }

        $todos = $this->db->get()->result_array();
        
        // format date
        $i = 0;

        foreach ($todos as $todo) {
            $todos[$i]['dateadded']    = _dt($todo['dateadded']);
            $todos[$i]['datefinished'] = _dt($todo['datefinished']);
            $todos[$i]['description']  = $todo['description'];
            $todos[$i]['attachments']  = $this->get_todo_attachments($todo['todoid']);
            $i++;
        }

        return $todos;
    }

    /**
     * Add new user todo
     * @param mixed $data todo $_POST data
     */
    public function add($data)
    {
        $data['dateadded']   = date('Y-m-d H:i:s');
        $data['description'] = nl2br($data['description']);
        $data['staffid']     = get_staff_user_id();
        $this->db->insert(db_prefix().'todos', $data);

        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['description'] = nl2br($data['description']);

        $this->db->where('todoid', $id);
        $this->db->update(db_prefix().'todos', $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Update todo's order / Ajax - Sortable
     * @param  mixed $data todo $_POST data
     */
    public function update_todo_items_order($data)
    {
        for ($i = 0; $i < count($data['data']); $i++) {
            $update = [
                'item_order' => $data['data'][$i][1],
                'finished'   => $data['data'][$i][2],
            ];
            if ($data['data'][$i][2] == 1) {
                $update['datefinished'] = date('Y-m-d H:i:s');
            }
            $this->db->where('todoid', $data['data'][$i][0]);
            $this->db->update(db_prefix().'todos', $update);
        }
    }

    /**
     * Delete todo
     * @param  mixed $id todo id
     * @return boolean
     */
    public function delete_todo_item($id)
    {
        $attachments = $this->get_todo_attachments($id);
        foreach ($attachments as $attachment) {
            $this->remove_todo_attachment($attachment['id']);
        }

        $this->db->where('todoid', $id);
        $this->db->where('staffid', get_staff_user_id());
        $this->db->delete(db_prefix().'todos');
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Change todo status / finished or not finished
     * @param  mixed $id     todo id
     * @param  integer $status can be passed 1 or 0
     * @return array
     */
    public function change_todo_status($id, $status)
    {
        $this->db->where('todoid', $id);
        $this->db->where('staffid', get_staff_user_id());
        $date = date('Y-m-d H:i:s');
        $this->db->update(db_prefix().'todos', [
            'finished'     => $status,
            'datefinished' => $date,
        ]);
        if ($this->db->affected_rows() > 0) {
            return [
                'success' => true,
            ];
        }

        return [
            'success' => false,
        ];
    }

    public function get_todo_attachments($todoid)
    {
        $this->db->select(implode(', ', prefixed_table_fields_array(db_prefix() . 'files')));
        $this->db->join(db_prefix() . 'todos', db_prefix() . 'todos.todoid = ' . db_prefix() . 'files.rel_id');
        $this->db->where(db_prefix() . 'files.rel_id', $todoid);
        $this->db->where(db_prefix() . 'files.rel_type', 'todo');
        $this->db->where(db_prefix() . 'todos.staffid', get_staff_user_id());
        $this->db->order_by(db_prefix() . 'files.dateadded', 'desc');

        return $this->db->get(db_prefix() . 'files')->result_array();
    }

    public function add_attachment_to_database($todoid, $attachment, $external = false)
    {
        return $this->misc_model->add_attachment_to_database($todoid, 'todo', $attachment, $external);
    }

    public function remove_todo_attachment($id)
    {
        $deleted = false;

        $this->db->select(db_prefix() . 'files.*');
        $this->db->from(db_prefix() . 'files');
        $this->db->join(db_prefix() . 'todos', db_prefix() . 'todos.todoid = ' . db_prefix() . 'files.rel_id');
        $this->db->where(db_prefix() . 'files.id', $id);
        $this->db->where(db_prefix() . 'files.rel_type', 'todo');
        $this->db->where(db_prefix() . 'todos.staffid', get_staff_user_id());
        $attachment = $this->db->get()->row();

        if ($attachment) {
            if (empty($attachment->external)) {
                $relPath  = get_upload_path_by_type('todo') . $attachment->rel_id . '/';
                $fullPath = $relPath . $attachment->file_name;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                    $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
                    $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $thumbPath = $relPath . $fname . '_thumb.' . $fext;
                    if (file_exists($thumbPath)) {
                        unlink($thumbPath);
                    }
                }
            }

            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Todo Attachment Deleted [TodoID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('todo') . $attachment->rel_id)) {
                $other_attachments = list_files(get_upload_path_by_type('todo') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    delete_dir(get_upload_path_by_type('todo') . $attachment->rel_id);
                }
            }
        }

        return ['success' => $deleted];
    }
}
