<?php defined('BASEPATH') or exit('No direct script access allowed');
$client_page_help_context = isset($client_page_help_context) ? $client_page_help_context : 'default';
?>
<?php if ($client_page_help_context === 'home') { ?>
<div class="alert alert-info" style="margin-bottom:25px;">
    <h3 class="bold" style="margin-top:0;">Project Setup &amp; Transaction Management</h3>
    <p>To ensure efficient processing and clear communication, all client activities within the platform are organized using the <strong>Projects</strong> system.</p>
    <h4 class="bold">Step 1: Create a Project</h4>
    <p>Before initiating any transaction or submitting documentation, you must first <strong>create a Project</strong>.</p>
    <ul style="padding-left:20px;">
        <li>Navigate to <strong>Projects</strong> from the top menu, or</li>
        <li>Click the <strong>New Project</strong> button below</li>
    </ul>
    <h4 class="bold">Project Naming Guidelines</h4>
    <p>Each Project should correspond directly to a specific transaction or service request. If you have multiple transactions, <strong>a separate Project must be created for each one</strong> to maintain proper structure and tracking.</p>
    <p><strong>Examples:</strong></p>
    <ul style="padding-left:20px;">
        <li>Standby Letter of Credit - <em>SBLC Transaction</em></li>
        <li>Letter of Credit - <em>Letter of Credit</em></li>
        <li>Diplomatic Status Processing - <em>Diplomatic Status</em></li>
    </ul>
    <h4 class="bold">Project Configuration</h4>
    <ol style="padding-left:20px;">
        <li>Select the appropriate <strong>Category</strong></li>
        <li>Choose the relevant <strong>Support / Service Type</strong></li>
        <li>Provide a <strong>brief summary</strong> describing the purpose and scope of the transaction</li>
    </ol>
    <h4 class="bold">Ongoing Communication</h4>
    <ul style="padding-left:20px;">
        <li>All documents, updates, and communications will be managed <strong>within that specific Project</strong></li>
        <li>Your Relationship Manager will correspond with you directly inside the Project thread</li>
        <li>This ensures all activity remains <strong>centralized, traceable, and efficiently managed</strong></li>
    </ul>
    <h4 class="bold">Why This Matters</h4>
    <ul style="padding-left:20px;">
        <li>Move efficiently through internal processing channels</li>
        <li>Maintain organized documentation and communication</li>
        <li>Reduce delays and miscommunication</li>
        <li>Support faster execution and response times</li>
    </ul>
    <p style="margin-bottom:20px;"><strong>Important:</strong> Projects serve as the official record for your transaction. Proper setup is essential to ensure timely processing and successful completion of your request.</p>
    <a href="<?php echo site_url('clients/new_project'); ?>" class="btn btn-info">New Project</a>
</div>
<?php } elseif ($client_page_help_context === 'projects') { ?>
<div class="alert alert-info">
    Choose the project that matches the transaction you are working on. If you have more than one transaction, create a separate project for each one so your files, updates, and support communication stay organized.
</div>
<?php } elseif ($client_page_help_context === 'project') { ?>
<div class="alert alert-info">
    This project is the main workspace for your transaction. Use the tabs in this page to upload files, review updates, open tickets, and keep all communication tied to the correct request.
</div>
<?php } elseif ($client_page_help_context === 'tickets') { ?>
<div class="alert alert-info">
    This page shows your support ticket history. For the best experience, open a project first and then create tickets that belong to that specific transaction so your relationship team can follow the full context.
</div>
<?php } elseif ($client_page_help_context === 'open_ticket') { ?>
<div class="alert alert-info">
    Before submitting a ticket, make sure it is connected to the correct project whenever possible. This helps the bank team review your request faster and keeps the transaction record complete.
</div>
<?php } elseif ($client_page_help_context === 'new_project') { ?>
<div class="alert alert-info">
    Use this form to create the official project record for your transaction. Choose the closest category, select the support or service type, and summarize exactly what you need done so the relationship team can route it correctly.
</div>
<?php } ?>
