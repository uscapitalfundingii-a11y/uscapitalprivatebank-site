<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Regulatorynotice extends ClientsController
{
    public function index($slug = '')
    {
        $documents = $this->regulatory_documents();
        $slug      = $slug ?: 'regulatory-notice';
        if (!isset($documents[$slug])) {
            $slug = 'regulatory-notice';
        }
        $current = $documents[$slug];

        $data['title']                 = $current['title'] . ' - ' . get_option('companyname');
        $data['documents']             = $documents;
        $data['current_document']       = $current;
        $data['current_document_slug']  = $slug;
        $data['regulatory_notice_url']  = site_url('regulatorynotice');

        $this->data($data);
        $this->view('regulatory_notice');
        $this->layout();
    }

    private function regulatory_documents()
    {
        return [
            'regulatory-notice' => [
                'title'       => 'Regulatory Notice',
                'eyebrow'     => 'Primary Disclosure Hub',
                'path'        => 'regulatorynotice',
                'group'       => 'Core Notice',
                'status'      => 'Primary hub',
                'summary'     => 'Central index for legal, compliance, risk, identity, and public notice disclosures.',
                'description' => 'This page organizes the public regulatory and legal disclosure set so clients, counterparties, and internal staff can move through the required notices without losing context.',
                'sections'    => [
                    [
                        'heading' => 'Purpose',
                        'body'    => [
                            'This hub is the controlled CRM landing page for public regulatory notices, legal disclosures, and policy references.',
                            'It is designed to support navigation and document discovery only. It does not change the meaning of any approved legal text.',
                        ],
                    ],
                    [
                        'heading' => 'Document Control',
                        'body'    => [
                            'Each linked page should retain its reviewed legal language and be approved through Aurora before publication or upload.',
                            'Questions about legal wording, regulatory interpretation, or customer-facing publication should be escalated before release.',
                        ],
                    ],
                ],
            ],
            'institutional-risk-disclosure' => [
                'title'       => 'Institutional Risk Disclosure',
                'eyebrow'     => 'Risk Disclosure',
                'path'        => 'institutional-risk-disclosure',
                'group'       => 'Risk',
                'status'      => 'Disclosure page',
                'summary'     => 'Risk category page for institutional transaction, counterparty, compliance, and operational considerations.',
                'description' => 'This CRM page provides a formal destination for the institutional risk disclosure and links it back to the full regulatory hub.',
            ],
            'corporate-registration' => [
                'title'       => 'Corporate Registration',
                'eyebrow'     => 'Identity Disclosure',
                'path'        => 'corporate-registration',
                'group'       => 'Identity',
                'status'      => 'Disclosure page',
                'summary'     => 'Reference page for registration and organizational identity notices.',
                'description' => 'This CRM page provides a formal destination for corporate registration disclosure references and related review language.',
            ],
            'corporate-identity-legal-independence' => [
                'title'       => 'Corporate Identity & Legal Independence',
                'eyebrow'     => 'Identity Disclosure',
                'path'        => 'corporate-identity-legal-independence',
                'group'       => 'Identity',
                'status'      => 'Disclosure page',
                'summary'     => 'Disclosure route for identity, naming, and legal independence references.',
                'description' => 'This CRM page organizes the identity and legal independence notice as part of the public regulatory set.',
            ],
            'compliance-aml-policy' => [
                'title'       => 'Compliance & AML Policy',
                'eyebrow'     => 'Compliance Policy',
                'path'        => 'compliance-aml-policy',
                'group'       => 'Compliance',
                'status'      => 'Policy page',
                'summary'     => 'Policy route for compliance, due diligence, AML, and financial crime prevention references.',
                'description' => 'This CRM page provides the compliance and AML policy destination and keeps it linked to the broader notice set.',
            ],
            'bank-to-bank-communication-policy' => [
                'title'       => 'Bank-to-Bank Communication Policy',
                'eyebrow'     => 'Communication Policy',
                'path'        => 'bank-to-bank-communication-policy',
                'group'       => 'Communication',
                'status'      => 'Policy page',
                'summary'     => 'Policy route for institution-to-institution communication and verification expectations.',
                'description' => 'This CRM page provides a formal route for communication policy content and supporting notices.',
            ],
            'reputation-protection-policy' => [
                'title'       => 'Reputation Protection Policy',
                'eyebrow'     => 'Public Notice',
                'path'        => 'reputation-protection-policy',
                'group'       => 'Public Notice',
                'status'      => 'Policy page',
                'summary'     => 'Public notice route for brand, reputation, impersonation, and misuse concerns.',
                'description' => 'This CRM page provides a formal destination for reputation protection and misuse notice content.',
            ],
            'official-clarification' => [
                'title'       => 'Official Clarification',
                'eyebrow'     => 'Public Clarification',
                'path'        => 'official-clarification',
                'group'       => 'Public Notice',
                'status'      => 'Clarification page',
                'summary'     => 'Public clarification route for correcting confusion around official identity and communications.',
                'description' => 'This CRM page provides an official clarification destination tied back to the regulatory hub.',
            ],
            'official-notice' => [
                'title'       => 'Official Notice',
                'eyebrow'     => 'Public Notice',
                'path'        => 'official-notice',
                'group'       => 'Public Notice',
                'status'      => 'Notice page',
                'summary'     => 'General public notice route for formal institutional notices.',
                'description' => 'This CRM page provides a formal destination for official notices and related public references.',
            ],
            'fraud-clarification' => [
                'title'       => 'Fraud Clarification',
                'eyebrow'     => 'Fraud Notice',
                'path'        => 'fraud-clarification',
                'group'       => 'Public Notice',
                'status'      => 'Clarification page',
                'summary'     => 'Clarification route for fraud, impersonation, and unauthorized representation concerns.',
                'description' => 'This CRM page provides a formal fraud clarification destination and links users back to the full disclosure set.',
            ],
            'privacy-policy' => [
                'title'       => 'Privacy Policy',
                'eyebrow'     => 'Privacy',
                'path'        => 'privacy-policy',
                'group'       => 'Legal',
                'status'      => 'Existing CRM policy',
                'summary'     => 'Existing CRM privacy policy page, preserved from stored CRM content.',
                'description' => 'This link opens the existing CRM Privacy Policy page without changing the stored policy text.',
                'external'    => true,
            ],
            'terms-and-conditions' => [
                'title'       => 'Terms and Conditions',
                'eyebrow'     => 'Terms',
                'path'        => 'terms-and-conditions',
                'group'       => 'Legal',
                'status'      => 'Existing CRM policy',
                'summary'     => 'Existing CRM terms page, preserved from stored CRM content.',
                'description' => 'This link opens the existing CRM Terms and Conditions page without changing the stored terms text.',
                'external'    => true,
            ],
            'sitemap' => [
                'title'       => 'Sitemap',
                'eyebrow'     => 'Navigation Support',
                'path'        => 'sitemap',
                'group'       => 'Navigation',
                'status'      => 'Support page',
                'summary'     => 'Navigation support route for the legal and regulatory disclosure set.',
                'description' => 'This CRM page provides a clean route map for the regulatory disclosure set and related public legal pages.',
            ],
        ];
    }
}
