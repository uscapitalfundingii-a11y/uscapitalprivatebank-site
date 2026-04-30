<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="__todo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <span class="edit-title hide"><?php echo _l('todo_edit_title'); ?></span>
                    <span class="add-title hide"><?php echo _l('todo_add_title'); ?></span>
                </h4>
            </div>
            <?php echo form_open_multipart('admin/todo/todo', ['id' => 'add_new_todo_item']); ?>
            <div class="modal-body">
                <div class="row">
                <?php echo form_hidden('todoid', ''); ?>
                    <div class="col-md-12">
                        <?php echo render_textarea('description', 'add_new_todo_description', ''); ?>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="todo-attachments"><?php echo _l('todo_add_attachments'); ?></label>
                            <input type="file" id="todo-attachments" name="attachments[]" class="form-control" multiple>
                            <p class="text-muted mtop5"><?php echo _l('todo_attachments_help'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div id="todo-existing-attachments" class="hide">
                            <h5 class="bold"><?php echo _l('todo_view_attachments'); ?></h5>
                            <div class="todo-attachment-list"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
