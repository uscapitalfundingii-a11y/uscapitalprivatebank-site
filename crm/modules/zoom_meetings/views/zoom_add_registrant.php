<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-5 left-column">
            <div class="panel_s">
               <div class="panel-body">
                  <?php echo form_open('zoom_meetings/zoom_meetings/submit_registrant', array('id' => 'meeting-submit-form')); ?>
                  
                  <div class="form-group projects-wrapper">
                     <div id="project_ajax_search_wrapper"></div>
                  </div>
                  
                  <div class="form-group">
                     <label for="zoom_registrant_meetid"><?php echo _l('zoom_registrant_meetid'); ?></label>
                     <select id="zoom_registrant_meetid" name="zoom_registrant_meetid" class="form-control selectpicker" data-live-search="true" required>
                        <option value=""><?php echo _l('select'); ?></option>
                        <!-- Options will be dynamically populated using JavaScript -->
                     </select>
                  </div>

                  <?php echo render_input('zoom_registrant_email', 'zoom_registrant_email', '', 'text', array('required' => 'true')); ?>

                  <div class="row">
                     <div class="col-md-6">
                        <?php echo render_input('zoom_registrant_fname', 'zoom_registrant_fname', '', 'text', array('required' => 'true')); ?>
                     </div>
                     <div class="col-md-6">
                        <?php echo render_input('zoom_registrant_lname', 'zoom_registrant_lname', '', 'text'); ?>
                     </div>
                  </div>

                  <div class="btn-bottom-toolbar text-right">
                     <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                  </div>
                  <?php echo form_close(); ?>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<?php init_tail(); ?>

<script>
   $(document).ready(function() {
      $.ajax({
         url: '<?php echo admin_url('zoom_meetings/get_user_meetings'); ?>',
         method: 'GET',
         success: function(response) {
            var meetings = JSON.parse(response);
            var meetingSelect = $('#zoom_registrant_meetid');
            if (meetings.length > 0) {
               meetings.forEach(function(meeting) {
                  meetingSelect.append('<option value="' + meeting.meeting_id + '">' + meeting.subject + ' (' + meeting.meeting_id + ')</option>');
               });
               meetingSelect.selectpicker('refresh');
            }
         }
      });
   });
</script>

</body>
</html>
