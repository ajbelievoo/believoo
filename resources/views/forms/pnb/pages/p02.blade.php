{{-- FORM PAGE 2 : 4.3-4.4 address, 5 contact, 6-9 business/account/services, 10-11 --}}
<div class="fpage">
  <div class="row nowrap">
    <span class="lbl">4.3 Principal place of Business (as same registered address)</span>
    <span class="lbl">Y</span><input class="cb" style="--n:1" maxlength="1" name="ppb_y">
    <span class="lbl">N</span><input class="cb" style="--n:1" maxlength="1" name="ppb_n">
    <span class="note">(if no please fill details as under)</span>
  </div>
  <div class="row">
    <span class="lbl">Address Type*:</span>
    <span><input type="checkbox" class="ck" name="a3_t_resbiz"><span class="small">RESIDENTIAL / BUSINESS</span></span>
    <span><input type="checkbox" class="ck" name="a3_t_res"><span class="small">RESIDENTIAL</span></span>
    <span><input type="checkbox" class="ck" name="a3_t_biz"><span class="small">BUSINESS</span></span>
    <span><input type="checkbox" class="ck" name="a3_t_reg"><span class="small">REGISTERED OFFICE</span></span>
    <span><input type="checkbox" class="ck" name="a3_t_unspec"><span class="small">UNSPECIFIED</span></span>
  </div>
  <div class="row">
    <span class="lbl">Proof of Address* :</span>
    <span><input type="checkbox" class="ck" name="a3_poa_cert"><span class="small">CERTIFICATE OF INCORPORATION / FORMATION</span></span>
    <span><input type="checkbox" class="ck" name="a3_poa_reg"><span class="small">REGISTRATION CERTIFICATE</span></span>
  </div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 1*:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="a3_l1"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 2:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="a3_l2"></div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">Line 3:</span><input class="cb" style="--n:26" maxlength="26" name="a3_l3">
    <span class="lbl">City/ Town/Village*:</span><input class="cb grow" style="--n:14;max-width:180px" maxlength="14" name="a3_city">
  </div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">District*:</span><input class="cb" style="--n:26" maxlength="26" name="a3_dist">
    <span class="lbl">Pin/Post Code*:</span><input class="cb" style="--n:8" maxlength="8" name="a3_pin">
  </div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">State / UT Name*:</span><input class="cb" style="--n:26" maxlength="26" name="a3_state">
    <div class="col"><span class="lbl">Country Code*:</span><span class="note">(ISO3166)</span></div><input class="cb" style="--n:3" maxlength="3" name="a3_country">
  </div>

  <div class="lbl mt4">4.4 Address in the jurisdiction where entity is resident outside India for tax purposes*</div>
  <div class="row">
    <span><input type="checkbox" class="ck big" name="a4_same_perm"><span class="small">SAME AS CURRENT / PERMANENT / OVERSEAS ADDRESS DETAILS</span></span>
    <span><input type="checkbox" class="ck big" name="a4_same_corr"><span class="small">SAME AS CORRESPONDENCE / LOCAL ADDRESS DETAILS</span></span>
  </div>
  <div class="row">
    <span class="lbl">Address Type*:</span>
    <span><input type="checkbox" class="ck" name="a4_t_resbiz"><span class="small">RESIDENTIAL / BUSINESS</span></span>
    <span><input type="checkbox" class="ck" name="a4_t_res"><span class="small">RESIDENTIAL</span></span>
    <span><input type="checkbox" class="ck" name="a4_t_biz"><span class="small">BUSINESS</span></span>
    <span><input type="checkbox" class="ck" name="a4_t_reg"><span class="small">REGISTERED OFFICE</span></span>
    <span><input type="checkbox" class="ck" name="a4_t_unspec"><span class="small">UNSPECIFIED</span></span>
  </div>
  <div class="row">
    <span class="lbl">Proof of Address (for entities registered outside India)* :</span>
    <span><input type="checkbox" class="ck" name="a4_poa_reg"><span class="small">REGISTRATION CERTIFICATE OR EQUIVALENT</span></span>
    <span><input type="checkbox" class="ck" name="a4_poa_cert"><span class="small">CERTIFICATE OF INCORPORATION/FORMATION</span></span>
  </div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 1*:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="a4_l1"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 2:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="a4_l2"></div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">Line 3:</span><input class="cb" style="--n:26" maxlength="26" name="a4_l3">
    <span class="lbl">City/ Town / Village*:</span><input class="cb grow" style="--n:14;max-width:180px" maxlength="14" name="a4_city">
  </div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">State*:</span><input class="cb" style="--n:14" maxlength="14" name="a4_state">
    <span class="lbl">Zip / Post Code*:</span><input class="cb" style="--n:8" maxlength="8" name="a4_zip">
    <div class="col"><span class="lbl">Country Code*:</span><span class="note">(ISO 3166 )</span></div><input class="cb" style="--n:3" maxlength="3" name="a4_country">
  </div>

  <div class="bar"><span>5. Contact Details (All communications will be sent on provided Mobile no./ Email- ID) (Please refer Instruction 'F' at the end)</span></div>
  <div class="row nowrap">
    <span class="lbl">Tel. (Off):</span><input class="cb" style="--n:4" maxlength="4" name="tel_off_std"><input class="cb" style="--n:8" maxlength="8" name="tel_off">
    <span class="lbl">Tel. (Res):</span><input class="cb" style="--n:4" maxlength="4" name="tel_res_std"><input class="cb" style="--n:8" maxlength="8" name="tel_res">
  </div>
  <div class="row nowrap">
    <span class="lbl">Fax:</span><input class="cb" style="--n:4" maxlength="4" name="fax_std"><input class="cb" style="--n:8" maxlength="8" name="fax">
  </div>
  <div class="row nowrap">
    <span class="lbl">Mobile 1:</span><input class="cb" style="--n:10" maxlength="10" name="mob1">
    <span class="lbl">Mobile 2:</span><input class="cb" style="--n:10" maxlength="10" name="mob2">
  </div>
  <div class="row nowrap"><span class="lbl">Email ID 1:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="email1"></div>
  <div class="row nowrap"><span class="lbl">Email ID 2:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="email2"></div>
  <div class="note mt2">In case Email ID/Mobile number is seeded in more than one customer id, please mandatorily provide the reason and your relationship with the account holder having same Email ID/Mobile number registered:</div>
  <div class="row nowrap"><span class="lbl">Reason</span><input class="ln grow" name="dup_reason"><span class="lbl">Relationship with account holder</span><input class="ln grow" name="dup_rel"></div>
  <div class="note">(One E-mail ID/Mobile number can be registered with a maximum of 5 Individual Cust IDs.)</div>
  <div class="mt2"><b>Declaration cum undertaking:</b></div>
  <div class="small">I declare that the email ID provided by me is not short lived self-disposable</div>
  <div class="row small nowrap">
    <span>I hereby provide explicit consent to PNB to send promotional SMS or make promotional calls regarding banking products and services. This consent may be withdrawn at any time by contacting the bank or via the designated opt-out mechanism.</span>
    <span><input type="checkbox" class="ck" name="sms_y"> Yes</span>
    <span><input type="checkbox" class="ck" name="sms_n"> No</span>
  </div>

  <div class="bar"><span>6. Nature of Business</span></div>
  <div class="row">
    <span><input type="checkbox" class="ck big" name="nb_mfg"><span class="small">MANUFACTURER</span></span>
    <span><input type="checkbox" class="ck big" name="nb_trader"><span class="small">TRADER</span></span>
    <span><input type="checkbox" class="ck big" name="nb_retail"><span class="small">RETAILER</span></span>
    <span><input type="checkbox" class="ck big" name="nb_service"><span class="small">SERVICE PROVIDER</span></span>
    <span><input type="checkbox" class="ck big" name="nb_export"><span class="small">EXPORT / IMPORT</span></span>
    <span><input type="checkbox" class="ck big" name="nb_other"><span class="small">OTHERS</span><input class="ln" style="width:120px" name="nb_other_txt"></span>
  </div>
  <div class="row nowrap">
    <span class="lbl">Industry Code*:</span><input class="cb" style="--n:2" maxlength="2" name="industry_code">
    <span class="note">(PLEASE REFER TO INDUSTRY CODES PAGE ON PAGE 5)</span>
    <span class="lbl">Others:</span><input class="ln grow" name="industry_other">
  </div>
  <div class="row nowrap">
    <span class="lbl">Annual Turnover Rs.</span><input class="ln" style="width:130px" name="turnover">
    <span class="note">PLEASE PROVIDE SUPPORTING DOCUMENTS OR SELF DECLARATION (Applicable upto certain limit as decided by Bank)</span>
  </div>
  <div class="row nowrap"><span class="lbl">Expected Annual Credit Rs.</span><input class="ln" style="width:150px" name="exp_credit"></div>
  <div class="row small nowrap" style="align-items:flex-start">
    <span class="lbl">MLM Undertaking:</span>
    <input type="checkbox" class="ck" name="mlm_no">
    <span style="flex:1">"I/We Declare that my/our Company/Firm is <b>not MLM (Multi Level Marketing) Company/Firm</b></span>
    <span>OR</span>
    <input type="checkbox" class="ck" name="mlm_yes">
    <span style="flex:1.6">"I/We declare that my/our Company/Firm is an <b>MLM</b> (Multi Level Marketing) <b>Company/Firm</b> and the Company is doing business of Multi-Level Marketing and has given an undertaking to the Department of Consumer Affairs that the Company is in compliance with Direct Selling Guidelines, 2016 issued by the Government of India, Ministry of Consumer Affairs, Food &amp; Public Distribution as also any direct selling guidelines issued by the State Government, where the registered office of the Company is located. Further, the Company is not in violation and undertake not to violate the provisions of Prize Chit and Money Circulation (Banning) Act, 1978."</span>
  </div>

  <div class="bar"><span>7. Type of Account</span></div>
  <div class="row">
    <span><input type="checkbox" class="ck big" name="ac_current"><span class="small">CURRENT ACCOUNT</span></span>
    <span><input type="checkbox" class="ck big" name="ac_savings"><span class="small">SAVINGS BANK ACCOUNT</span></span>
    <span><input type="checkbox" class="ck big" name="ac_other"><span class="small">OTHER</span></span>
    <span class="lbl">Please specify:</span><input class="ln grow" name="ac_other_txt">
  </div>
  <div class="row">
    <span><input type="checkbox" class="ck big" name="ac_rd"><span class="small">RECURRING DEPOSIT</span></span>
    <span><input type="checkbox" class="ck big" name="ac_sweep"><span class="small">SWEEP FACILITY</span></span>
  </div>
  <div class="row nowrap">
    <span class="lbl">Scheme Type</span><input class="ln" style="width:110px" name="ac_scheme">
    <span class="lbl">Amount</span><input class="ln" style="width:90px" name="ac_amount">
    <input type="checkbox" class="ck big" name="ac_period_ck">
    <span class="lbl">Period</span><input class="ln" style="width:80px" name="ac_period">
    <span class="lbl">Auto Renewal</span>
    <span><input type="checkbox" class="ck big" name="ar_yes"> YES</span>
    <span><input type="checkbox" class="ck big" name="ar_no"> NO</span>
  </div>

  <div class="bar"><span>8. Mode of Operations</span></div>
  <div class="row">
    <span><input type="checkbox" class="ck big" name="mo_single"><span class="small">SINGLY</span></span>
    <span><input type="checkbox" class="ck big" name="mo_joint"><span class="small">JOINTLY</span></span>
    <span><input type="checkbox" class="ck big" name="mo_several"><span class="small">SEVERALLY</span></span>
    <span><input type="checkbox" class="ck big" name="mo_board"><span class="small">AS PER BOARD RESOLUTION</span></span>
    <span><input type="checkbox" class="ck big" name="mo_other"><span class="small">OTHERS : ( PLEASE SPECIFY)</span><input class="ln grow" style="min-width:150px" name="mo_other_txt"></span>
  </div>

  <div class="bar"><span>9. Services Required (Tick the required service (Charges may be applicable))</span></div>
  <div class="row nowrap">
    <span class="lbl">Corporate Internet Banking :</span><span class="small">VIEWING ONLY</span><input type="checkbox" class="ck big" name="cib_view">
    <span class="small">VIEW &amp; TRANSACTION</span><input type="checkbox" class="ck big" name="cib_trans">
    <span class="grow"></span><span class="small">CHEQUE BOOK</span><input type="checkbox" class="ck big" name="svc_cheque">
    <span class="grow"></span><span class="small">DEBIT CARD</span><input type="checkbox" class="ck big" name="svc_debit">
  </div>
  <div class="row nowrap">
    <span class="grow"></span><span class="small">POS FACILITY (CARD SWIPING MACHINE)</span><input type="checkbox" class="ck big" name="svc_pos">
    <span class="small">SMS ALERTS</span><input type="checkbox" class="ck big" name="svc_sms">
    <span class="grow"></span><span class="small">DOOR STEP BANKING</span><input type="checkbox" class="ck big" name="svc_door">
    <span class="grow"></span><span class="small">PAY FEE FACILITY</span><input type="checkbox" class="ck big" name="svc_payfee">
  </div>
  <div class="row nowrap">
    <span class="grow"></span><span class="small">PLATINUM DEBIT CARD</span><input type="checkbox" class="ck big" name="svc_platinum">
    <span class="grow"></span><span class="grow"></span><span class="small">OTHER</span><input class="ln" style="width:110px" name="svc_other">
  </div>
  <div class="row nowrap">
    <span class="lbl">Statement Frequency:</span><span class="small">MONTHLY</span><input type="checkbox" class="ck big" name="stmt_m">
    <span class="grow"></span><span class="small">QUARTERLY</span><input type="checkbox" class="ck big" name="stmt_q">
    <span class="grow"></span><span class="small">HALF-YEARLY</span><input type="checkbox" class="ck big" name="stmt_h">
    <span class="grow"></span><span class="small">DAILY</span><input type="checkbox" class="ck big" name="stmt_d">
  </div>
  <div class="row nowrap"><span class="lbl">E-statement to be sent to Email ID :</span><input class="cb grow" style="--n:44;width:100%" maxlength="44" name="estmt_email"></div>
  <div class="row nowrap"><span class="lbl">SMS alerts to be sent on : Mobile</span><input class="cb" style="--n:10" maxlength="10" name="sms_mob"><span class="note">(PLEASE REFER TO THE MOBILE NUMBERS GIVEN IN CONTACT DETAILS IN AOF PART 1)</span></div>

  <div class="bar"><span>10. Account Variant</span></div>
  <div class="row nowrap"><span class="lbl">Account Variant Name:</span><input class="ln grow" name="variant"></div>
  <div class="note">(FOR MORE DETAILS, PLEASE VISIT OUR WEBSITE OR VISIT NEAREST BRANCH)</div>

  <div class="bar"><span>11. Undertaking : Credit Facility from Banking System</span></div>
  <div class="flex">
    <div class="grow">
      <div class="row nowrap">
        <span class="small">AVAILING CREDIT FACILITY FROM BANKING SYSTEM.</span>
        <span><input type="checkbox" class="ck big" name="credit_no"> NO</span>
        <span><input type="checkbox" class="ck big" name="credit_yes"> YES</span>
      </div>
      <div class="small">(IF YES, PLEASE FILL ANNEXURE V)</div>
      <div class="small mt2">I/we undertake to inform you immediately if and when the sum of my/our availed credit facility(ies) becomes Rs. 5.00 crore or more (As amended from time to time)<br>
      I/We also understand that when the credit exposure is Rs. 5.00 crore or more(As amended from time to time), my/our account shall be governed by RBI Guidelines</div>
    </div>
    <div class="sigbox" style="width:170px">Signature of Applicant/s</div>
  </div>

  <div class="pgnum">2</div>
</div>
