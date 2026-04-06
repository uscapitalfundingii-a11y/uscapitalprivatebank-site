<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Proof of Funds</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            margin: 30px;
        }
        .header {
            width: 100%;
            margin-bottom: 10px;
        }
        .logo {
            float: left;
            width: 150px;
        }
        .address {
            float: right;
            width: 50%;
            text-align: right;
            font-size: 10pt;
            line-height: 1.2;
        }
        .clearfix {
            clear: both;
        }
        .notice-of-jurisdiction {
            font-family: "Times New Roman", serif;
            font-size: 5.5pt;
            font-style: italic;
            width: 33%;
            float: right;
            margin-top: 5px;
        }
        .notice-title {
            font-family: "Times New Roman", serif;
            font-size: 8pt;
            font-weight: bold;
            font-style: italic;
            margin-bottom: 3px;
        }
        .date {
            margin-top: 70px;
            font-weight: bold;
            font-size: 12pt;
        }
        .title {
            color: blue;
            font-family: Arial, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .certificate {
            float: right;
            text-align: right;
            font-family: Arial, sans-serif;
            font-weight: bold;
            color: red;
            font-size: 10pt;
        }
        .certificate input {
            width: 150px;
            font-weight: normal;
            font-size: 10pt;
        }
        .info-block {
            clear: both;
            margin-top: 40px;
            font-size: 11pt;
            line-height: 1.4;
        }
        .info-label {
            font-weight: bold;
            width: 160px;
            display: inline-block;
            vertical-align: top;
        }
        .signature-block {
            margin-top: 80px;
            width: 100%;
        }
        .signature-left,
        .signature-right {
            width: 45%;
            display: inline-block;
            text-align: center;
            font-size: 10pt;
        }
        .qr-code {
            width: 50px;
            height: 50px;
            display: inline-block;
            vertical-align: middle;
            margin: 0 5%;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="logo">
        <?= pdf_logo_url(); ?>
    </div>

    <div class="address">
        OPAL TOWER, SUITE 409<br />
        DOCUMENT PROCESSING CENTER<br />
        BUSINESS BAY, DUBAI UAE 00000
    </div>

    <div class="notice-of-jurisdiction">
        <div class="notice-title">Notice of Jurisdiction:</div>
        <p>
            <strong><em>Sovereign State under Divine Law</em></strong><br />
            "A sovereign state is generally defined to be any nation or people, whatever may be the form of its internal constitution, which governs itself independently of foreign powers. The supreme, absolute, and uncontrollable power by which an independent state is governed and from which all specific political powers are derived; the intentional independence of a state, combined with the right and power of regulating its internal affairs without foreign interference and the capacity to enter into <a href="http://en.wikipedia.org/wiki/Sovereign_state#cite_note-2" target="_blank" style="color:blue; text-decoration:underline;">sovereign states</a>.<br />
            It is also normally understood to be a state which is neither dependent on nor subject to any other power nor state. While according to State recognition, a sovereign state can exist without being recognized by other sovereign states, unrecognized states will often find it hard to exercise full treaty-making powers and engage in <a href="http://en.wikipedia.org/wiki/International_relations" target="_blank" style="color:blue; text-decoration:underline;">relations</a> with other sovereign states.<br />
            Some non-<a href="http://en.wikipedia.org/wiki/Diplomatic_recognition" target="_blank" style="color:blue; text-decoration:underline;">recognised</a> sovereign states are recorded and recognized under the United Nations General Assembly observers / divine law and or the Holy See. The word <a href="http://en.wikipedia.org/wiki/Diplomacy" target="_blank" style="color:blue; text-decoration:underline;">diplomatic</a>..." 
        </p>
    </div>
    <div class="clearfix"></div>
</div>

<div class="date">
    <?= date('d F Y'); ?>
</div>

<div class="certificate">
    CERTIFICATE NO.<br />
    <input type="text" value="" placeholder="Enter certificate number" />
</div>

<div class="title">Confirmation of Funds/Assets</div>

<div class="info-block">
    <div><span class="info-label">ACCOUNT NAME:</span> <?= isset($proposal->account_name) ? htmlspecialchars($proposal->account_name) : '[Account Name]'; ?></div>
    <div><span class="info-label">ADDRESS:</span> <?= isset($proposal->account_address) ? nl2br(htmlspecialchars($proposal->account_address)) : '[Account Address]'; ?></div>
    <div><span class="info-label">ACCOUNT NUMBER:</span> <?= isset($proposal->account_number) ? htmlspecialchars($proposal->account_number) : '[Account Number]'; ?></div>
    <div><span class="info-label">ACCOUNT SIGNATORY:</span> <?= isset($proposal->account_signatory) ? htmlspecialchars($proposal->account_signatory) : '[Account Signatory]'; ?></div>
    <div><span class="info-label">AMOUNT:</span> <?= isset($proposal->amount) ? htmlspecialchars($proposal->amount) : '€2,000,000.00 (TWO MILLION EUROS)'; ?></div>
</div>

<div class="info-block" style="margin-top: 30px; font-size: 10pt;">
    <p>
        AS OFFICIALS OF U.S. CAPITAL PRIVATE BANK / U.S.C.P.B., ETO AS CONFIRMED BY THE UNDERSIGNED AUTHORIZED SIGNATORIES, WITH FULL BANK RESPONSIBILITY AND LAWFUL AUTHORITY, THAT WE ARE HOLDING FUNDS/ASSETS IN OUR INSTITUTIONAL ACCOUNT(S) FOR THE ACCOUNT MENTIONED EARLIER HOLDER. WE FURTHER CONFIRM THAT AS OF THE ABOVE DATE WE HAVE ON DEPOSIT IN THIS ACCOUNT FUNDS/ASSETS OF <?= isset($proposal->amount) ? htmlspecialchars($proposal->amount) : '€2,000,000.00 (TWO MILLION EUROS)'; ?>.  
    </p>
    <p>
        WE FURTHER CONFIRM THAT TO THE BEST OF OUR KNOWLEDGE, THESE FUNDS ARE LAWFULLY EARNED, GOOD, CLEAN, AND CLEARED FUNDS, FREE FROM ANY LIENS ENCUMBRANCES, OR BLOCKS. THESE FUNDS ARE OF NON-CRIMINAL ORIGIN AND HAVE PASSED OUR INTERNAL FED-SCAN SYSTEM ANALYSIS. SAID FUNDS WILL BE BLOCKED AND AVAILABLE FOR TRADING.
    </p>
    <p>
        THIS LETTER PUTS NO FINANCIAL OBLIGATION ON SAID FUNDS AND IS VALID FROM THE ABOVE-ISSUED DATE AND VERIFIABLE BY RESPONSIBLE BANK INQUIRY.  CONTACT EITHER OF THE TWO OFFICERS BELOW AT TRUSTEE@USCAPITALPRIVATEBANK.COM
    </p>
</div>

<div class="signature-block">
    <div class="signature-left">
        ___________________________<br />
        Officer Printed Name and PIN<br />
        Signature
    </div>

    <div class="qr-code">
        <!-- QR code inserted automatically by Perfex here -->
    </div>

    <div class="signature-right">
        ___________________________<br />
        Officer Printed Name and PIN<br />
        Signature
    </div>
</div>

</body>
</html>
