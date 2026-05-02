<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: USCAP MCP Bridge
Description: Narrow token-protected API endpoints for Morpheus CRM onboarding operations.
Version: 0.1.0
Requires at least: 3.2.*
*/

define('USCAP_MCP_BRIDGE_MODULE', 'uscap_mcp_bridge');

register_activation_hook(USCAP_MCP_BRIDGE_MODULE, 'uscap_mcp_bridge_activation_hook');

function uscap_mcp_bridge_activation_hook(): void
{
    add_option('uscap_mcp_bridge_token', '');
    add_option('uscap_mcp_bridge_webhook_secret', '');
    add_option('uscap_mcp_bridge_enabled', '1');
}
