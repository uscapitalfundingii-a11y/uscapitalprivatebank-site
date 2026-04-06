<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Proof of Funds - US Capital Private Bank</title>
<style>
  body {
    font-family: "Times New Roman", serif;
    margin: 40px;
    position: relative;
    color: #000;
  }

  header {
    height: 1in;
    line-height: 1in;
    font-weight: bold;
    font-size: 14px;
    text-align: center;
    border-bottom: 1px solid #000;
    margin-bottom: 10px;
  }

  .top-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 5px;
  }

  .logo {
    max-height: 80px;
    margin-top: 10px;
  }

  .address {
    font-size: 12px;
    text-align: right;
    max-width: 350px;
    line-height: 1.2;
    margin-top: 10px;
    white-space: pre-line;
  }

  .notice-container {
    width: 33%;
    font-family: "Times New Roman", serif;
    font-size: 8pt;
    font-weight: bold;
    font-style: italic;
    margin-left: auto;
    text-align: left;
    margin-bottom: 15px;
  }

  .notice-text {
    font-weight: normal;
    font-size: 5.5pt;
    font-style: italic;
    margin-top: 5px;
    line-height: 1.1;
  }

  .notice-text a {
    color: blue;
    text-decoration: underline;
  }

  .date {
    margin-top: 30px;
    font-size: 12pt;
    font-family: Arial, sans-serif;
    font-weight: normal;
  }

  .certificate {
    position: absolute;
    top: 70px;
    right: 40px;
    font-family: Arial, sans-serif;
    font-weight: bold;
    font-size: 10pt;
    color: red;
  }

  .certificate input {
    font-weight: normal;
    font-size: 10pt;
    width: 160px;
    margin-top: 5px;
  }

  h1.title {
    font-family: Arial, sans-serif;
    font-size: 11pt;
    font-weight: bold;
    color: blue;
    margin-top: 40px;
    margin-bottom: 20px;
  }

  .account-info {
    font-family: "Times New Roman", serif;
    font-size: 12pt;
    line-height: 1.4;
    max-width: 700px;
    margin-bottom: 30px;
  }

  .account-info label {
    font-weight: bold;
    display: inline-block;
    width: 150px;
    vertical-align: top;
  }

  .body-text {
    font-family: "Times New Roman", serif;
    font-size: 11pt;
    line-height: 1.5;
    max-width: 700px;
    margin-bottom: 60px;
  }

  .signature-qr-container {
    max-width: 700px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: "Times New Roman", serif;
    font-size: 11pt;
    margin-top: 60px;
  }

  .signature-block {
    width: 30%;
    text-align: center;
  }

  .signature-line {
    border-bottom: 1px solid #000;
    padding-bottom: 5px;
    margin-bottom: 5px;
  }

  .qr-placeholder {
    width: 50px;
    height: 50px;
  }

  .watermark {
    position: fixed;
    top: 50%;
    left: 50%;
    width: 300px;
    height: 300px;
    opacity: 0.1;
    pointer-events: none;
    transform: translate(-50%, -50%);
    z-index: 0;
    background: url('watermarklogo.png') no-repeat center center;
    background-size: contain;
  }
</style>
</head>
<body>

<header>
  An Irrevocable Banking Institutional Express Trust<br />
  www.uscapitalprivatebank.com
</header>

<div class="top-row">
  <img src="path/to/logo.png" alt="US Capital Private Bank Logo" class="logo" />

  <div class="address" style="white-space: pre-line;">
    OPAL TOWER, SUITE 409
    DOCUMENT PROCESSING CENTER
    BUSINESS BAY, DUBAI UAE 00000
  </div>
</div>

<div class="notice-container">
  Notice of Jurisdiction:<br />
  Sovereign State under Divine Law
  <div class="notice-text">
    "A <a href="http://en.wikipedia.org/wiki/Sovereign_state#cite_note-2" target="_blank" rel="noopener noreferrer">sovereign state</a> is generally defined to be any nation or people, whatever may be the form of its internal constitution, which governs itself independently of foreign powers. The supreme, absolute, and uncontrollable power by which an independent state is governed and from which all specific political powers are derived; the intentional independence of a state, combined with the right and power of regulating its internal affairs without foreign interference.and the capacity to enter into <a href="http://en.wikipedia.org/wiki/International_relations" target="_blank" rel="noopener noreferrer">relations</a> with other sovereign states.[2] It is also normally understood to be a state which is neither dependent on nor subject to any other power nor state.<a href="http://en.wikipedia.org/wiki/Diplomatic_recognition" target="_blank" rel="noopener noreferrer"> recognised</a> states will often find it hard to exercise full treaty-making powers and engage in <a href="http://en.wikipedia.org/wiki/Diplomacy" target="_blank" rel="noopener noreferrer">diplomatic</a> relations with other sovereign states. Some non-recognized sovereign states are recorded and recognized under the United Nations General Assembly observers / divine law and or the Holy See."
  </div>
</div>

<div class="certificate">
  CERTIFICATE NO.<br />
  <input type="text" name="certificate_no" placeholder="Enter certificate number" maxlength="12" />
</div>

<div class="date">
  <?php echo date('d F Y'); ?>
</div>

<h1 class="title">Confirmation of Funds/Assets</h1>

<div class="account-info">
  <div><label>ACCOUNT NAME:</label> <span><?php echo htmlspecialchars($account_name ?? ''); ?></span></div>
  <div><label>ADDRESS:</label> <span style="white-space: pre-line;"><?php echo nl2br(htmlspecialchars($account_address ?? '')); ?></span></div>
  <div><label>ACCOUNT NUMBER:</label> <span><?php echo htmlspecialchars($account_number ?? ''); ?></span></div>
  <div><label>ACCOUNT SIGNATORY:</label> <span><?php echo htmlspecialchars($account_signatory ?? ''); ?></span></div>
  <div><label>AMOUNT:</label> <span><?php echo htmlspecialchars($amount_formatted ?? ''); ?></span></div>
</div>

<div class="body-text">
  AS OFFICIALS OF U.S. CAPITAL PRIVATE BANK / U.S.C.P.B., ETO AS CONFIRMED BY THE UNDERSIGNED AUTHORIZED SIGNATORIES, WITH FULL BANK RESPONSIBILITY AND LAWFUL AUTHORITY, THAT WE ARE HOLDING FUNDS/ASSETS IN OUR INSTITUTIONAL ACCOUNT(S) FOR THE ACCOUNT MENTIONED EARLIER HOLDER. WE FURTHER CONFIRM THAT AS OF THE ABOVE DATE WE HAVE ON DEPOSIT IN THIS ACCOUNT FUNDS/ASSETS OF <?php echo htmlspecialchars($amount_formatted ?? ''); ?>.   
  <br><br>
  WE FURTHER CONFIRM THAT TO THE BEST OF OUR KNOWLEDGE, THESE FUNDS ARE LAWFULLY EARNED, GOOD, CLEAN, AND CLEARED FUNDS, FREE FROM ANY LIENS ENCUMBRANCES, OR BLOCKS. THESE FUNDS ARE OF NON-CRIMINAL ORIGIN AND HAVE PASSED OUR INTERNAL FED-SCAN SYSTEM ANALYSIS. SAID FUNDS WILL BE BLOCKED AND AVAILABLE FOR TRADING.
  <br><br>
  THIS LETTER PUTS NO FINANCIAL OBLIGATION ON SAID FUNDS AND IS VALID FROM THE ABOVE-ISSUED DATE AND VERIFIABLE BY RESPONSIBLE BANK INQUIRY.  CONTACT EITHER OF THE TWO OFFICERS BELOW AT TRUSTEE@USCAPITALPRIVATEBANK.COM
</div>

<div class="signature-qr-container">
  <div class="signature-block">
    <div class="signature-line">&nbsp;</div>
    Officer Printed Name and PIN
  </div>

  <div class="qr-placeholder"></div>

  <div class="signature-block">
    <div class="signature-line">&nbsp;</div>
    Signature
  </div>
</div>

<div class="signature-qr-container">
  <div class="signature-block">
    <div class="signature-line">&nbsp;</div>
    Officer Printed Name and PIN
  </div>

  <div style="width: 50px;"></div> <!-- spacer -->

  <div class="signature-block">
    <div class="signature-line">&nbsp;</div>
    Signature
  </div>
</div>

<div class="watermark"></div>

</body>
</html>
