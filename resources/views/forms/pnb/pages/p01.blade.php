{{-- FORM PAGE 1 : Entity details, PoI, related persons, PoA 4.1-4.2 --}}
<div class="fpage">
  <img class="logo" src="{{ asset('images/forms/pnb-logo.png') }}" alt="Punjab National Bank">

  <div class="hdrrow">
    <div class="lbl">Branch <input class="ln" style="width:170px"></div>
    <div class="lbl">Solid <input class="ln" style="width:130px"></div>
    <div class="lbl">Date <input class="ln" style="width:110px"></div>
  </div>

  <div class="title">COMMON CIF CUM ACCOUNT OPENING FORM FOR ALL PUBLIC SECTOR BANKS (NON INDIVIDUAL)</div>

  <div class="flex">
    <div class="bordered" style="flex:1.35">
      <div class="row nowrap">
        <span class="lbl">Application Type*:</span>
        <input type="checkbox" class="ck big" name="app_new"><span class="lbl">New</span>
        <input type="checkbox" class="ck big" name="app_update"><span class="lbl">Update</span>
        <input class="cb" style="--n:8" maxlength="8" name="app_type_code">
        <span class="lbl bordered" style="padding:2px 5px">For office use only</span>
      </div>
      <div class="row nowrap"><span class="lbl" style="width:150px">CIF No.</span><input class="cb" style="--n:8" maxlength="8" name="cif_no"></div>
      <div class="row nowrap">
        <span class="lbl" style="width:150px">A/C No.</span><input class="cb" style="--n:15" maxlength="15" name="ac_no">
        <span class="lbl">Scheme Type</span><input class="cb" style="--n:10" maxlength="10" name="scheme_type">
      </div>
      <div class="row nowrap">
        <span class="lbl">CKYC Number (mandatory for CKYC update request):</span>
        <input class="cb" style="--n:14" maxlength="14" name="ckyc_no">
      </div>
      <div class="row nowrap">
        <span class="lbl">Account Holder Type*:</span>
        <input class="cb" style="--n:2" maxlength="2" name="aht_us"><span class="lbl">US Reportable</span>
        <input class="cb" style="--n:2" maxlength="2" name="aht_other"><span class="lbl">Other Reportable</span>
      </div>
      <div class="note">(Please refer to General Instructions point 'A')</div>
    </div>
    <div class="ibox" style="flex:1">
      <div><b>A.</b> Fields marked with '*' are mandatory fields.</div>
      <div><b>B.</b> Tick '☑' wherever applicable.</div>
      <div><b>C.</b> Please fill the date in DD-MM-YYYY format.</div>
      <div><b>D.</b> Please fill the Form in English and in BLOCK Letters.</div>
      <div><b>E.</b> Please read section wise detailed guidelines / Instructions</div>
      <div><b>F.</b> List of two character ISO 3166 country codes and List of State/U.T Code as per Indian Motor Vehicle Act, 1988 is available in the General Instructions.</div>
      <div><b>G.</b> For particular section update, please tick (√) in the box available before the section number and strike for the sections not required to be updated.</div>
      <div><b>H.</b> KYC number is Mandatory for Update Application</div>
      <div><b>I.</b> Definition of Important Terms are at the End</div>
      <div class="bordered mt2" style="border-width:1px"><b>Kindly fill the Annexure V first to check your eligibility to open Current Account as per the RBI Guidelines</b></div>
    </div>
  </div>

  <div class="row mt4">
    <input type="checkbox" class="ck big" name="no_ac_pnb"><span class="lbl">I/We do not have any account with PNB</span>
    <b>OR</b>
  </div>
  <div class="row nowrap">
    <input type="checkbox" class="ck big" name="have_ac_pnb"><span class="lbl">I/We have an account with PNB &amp; the account number is</span>
    <input class="cb" style="--n:16" maxlength="16" name="existing_ac">
  </div>

  <div class="bar"><span>1. Entity Details* (Please refer General Instructions Point 'C')</span></div>
  <div class="row nowrap">
    <div class="col"><span class="lbl">Name of the Entity*:</span><span class="note">(In block letters)</span></div>
    <div class="col grow">
      <input class="cb big grow" style="--n:52;width:100%" maxlength="52" name="entity_name_1">
      <input class="cb big grow" style="--n:52;width:100%" maxlength="52" name="entity_name_2">
    </div>
  </div>
  <div class="row nowrap mt2">
    <span class="grow"></span>
    <span class="lbl">Identification Type*:</span><input class="cb" style="--n:1" maxlength="1" name="id_type">
    <span class="note">(Please refer General Instructions 'C2'), if O-others (specify)</span><input class="ln" style="width:80px" name="id_type_other">
  </div>
  <div class="row nowrap">
    <span class="lbl">PAN*:</span><input class="cb" style="--n:10" maxlength="10" name="pan">
    <span class="note">(For entities tax resident of India only, PAN is equivalent to TIN)</span>
    <input class="cb" style="--n:9" maxlength="9" name="pan2"><span class="note">(Refer General Instructions)</span>
  </div>
  <div class="row nowrap">
    <span class="lbl">OR Form 60</span><input class="cb" style="--n:1" maxlength="1" name="form60">
    <span class="note">(For entities other then companies and partnerships)</span>
    <span class="lbl">NPO (non-profit organization)</span>
    <span class="lbl">Y</span><input class="cb" style="--n:1" maxlength="1" name="npo_y">
    <span class="lbl">N</span><input class="cb" style="--n:1" maxlength="1" name="npo_n">
    <span class="note">If Yes, specify Registration no.</span><input class="ln grow" name="npo_reg">
  </div>

  <div class="bar"><span>2. Proof of Identity (PoI)* (Please refer 'D' in General Instructions)</span></div>
  <div class="row nowrap">
    <span class="lbl">Place of Incorporation/ Formation*:</span><input class="ln" style="width:190px" name="incorp_place">
    <span class="lbl">Date of Incorporation/ Formation*:</span><input class="cb" style="--n:8" maxlength="8" name="incorp_date">
  </div>
  <div class="row nowrap">
    <span class="lbl">Country of Incorporation/ Formation* (code- ISO 3166):</span><input class="cb" style="--n:2" maxlength="2" name="incorp_country">
    <span class="note">(Refer General Instructions)</span>
    <span class="lbl">Date of Commencement of Business*:</span><input class="cb" style="--n:8" maxlength="8" name="biz_start">
    <span class="note">(Applicable in case of Public Limited Companies)</span>
  </div>
  <div class="row nowrap">
    <span class="lbl">GSTN :</span><input class="cb" style="--n:15" maxlength="15" name="gstn">
    <span class="lbl">Entity Constitution Type*:</span><input class="cb" style="--n:1" maxlength="1" name="const_type">
    <span class="note">(Please refer instruction B in General Instructions)</span>
  </div>
  <div class="row nowrap">
    <span class="lbl">CIN:</span><input class="cb" style="--n:21" maxlength="21" name="cin">
    <span class="note">(Only applicable in case of a company)</span>
  </div>
  <div class="row" style="gap:14px 10px">
    <span><input type="checkbox" class="ck big" name="poi_cert_inc"><span class="small">CERTIFICATE OF INCORPORATION / FORMATION</span></span>
    <span><input type="checkbox" class="ck big" name="poi_poa"><span class="small">POWER OF ATTORNEY GRANTED TO ITS MANAGER, OFFICERS OR EMPLOYEES TO TRANSACT ON ITS BEHALF</span></span>
    <span><input type="checkbox" class="ck big" name="poi_reg_cert"><span class="small">REGISTRATION CERTIFICATE</span></span>
    <span><input type="checkbox" class="ck big" name="poi_ovd"><span class="small">OFFICIALLY VALID DOCUMENT(S) IN RESPECT OF PERSON AUTHORIZED TO TRANSACT</span></span>
    <span><input type="checkbox" class="ck big" name="poi_resolution"><span class="small">RESOLUTION OF BOARD / MANAGING COMMITTEE</span></span>
    <span><input type="checkbox" class="ck big" name="poi_other"><span class="small">OTHER</span><input class="ln" style="width:120px" name="poi_other_txt"></span>
    <span><input type="checkbox" class="ck big" name="poi_moa"><span class="small">MEMORANDUM AND ARTICLE OF ASSOCIATION / PARTNERSHIP DEED/ TRUST DOCUMENT</span></span>
    <span><input type="checkbox" class="ck big" name="poi_activity"><span class="small">ACTIVITY PROOF ( FOR SOLE PROPRIETORSHIP ONLY)</span></span>
    <span><input type="checkbox" class="ck big" name="poi_urc"><span class="small">URC (Udhyam Registration certificate )</span></span>
  </div>

  <div class="bar"><span>3. Details of Related Person/ Beneficial Owner*<br><span class="sub">( An 'Annexure II' to be filled for each related person please refer point 'G' in General Instructions)</span></span></div>
  <div class="row nowrap">
    <span class="lbl">Number of Related Persons*:</span><input class="cb" style="--n:2" maxlength="2" name="num_rp">
    <span class="note">(A related person can be Director, Promoter, Karta, Trustee, Partner, Authorised Signatory, Beneficiary, Beneficial Owner, Court Appointed Official)</span>
  </div>
  <div class="row nowrap">
    <span class="lbl">Number of Beneficial Owners*:</span><input class="cb" style="--n:2" maxlength="2" name="num_bo">
    <span class="note">(Though a beneficial owner is a related person, the number of beneficial owner should be determined separately out of number of related person , beneficial owner is a part / subset of related person) (For definition see page no. 18)</span>
  </div>

  <div class="bar"><span>4. Proof of Address (PoA)* (Certified copies of the documents, as applicable, need to be submitted) (Please see instruction 'E' at the end)</span></div>
  <div class="lbl">4.1 Current / Permanent/Overseas Address Details*</div>
  <div class="row"><input type="checkbox" class="ck big" name="a1_regoffice"><span class="small">REGISTERED OFFICE ADDRESS IN INDIA (IF APPLICABLE)/ PLACE OF BUSINESS*</span></div>
  <div class="row">
    <span class="lbl">Address Type*:</span>
    <span><input type="checkbox" class="ck" name="a1_t_resbiz"><span class="small">RESIDENTIAL / BUSINESS</span></span>
    <span><input type="checkbox" class="ck" name="a1_t_res"><span class="small">RESIDENTIAL</span></span>
    <span><input type="checkbox" class="ck" name="a1_t_biz"><span class="small">BUSINESS</span></span>
    <span><input type="checkbox" class="ck" name="a1_t_reg"><span class="small">REGISTERED OFFICE</span></span>
    <span><input type="checkbox" class="ck" name="a1_t_unspec"><span class="small">UNSPECIFIED</span></span>
  </div>
  <div class="row">
    <span class="lbl">Proof of Address* :</span>
    <span><input type="checkbox" class="ck" name="a1_poa_cert"><span class="small">CERTIFICATE OF INCORPORATION / FORMATION</span></span>
    <span><input type="checkbox" class="ck" name="a1_poa_reg"><span class="small">REGISTRATION CERTIFICATE</span></span>
  </div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 1*:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="a1_l1"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 2:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="a1_l2"></div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">Line 3:</span><input class="cb" style="--n:26" maxlength="26" name="a1_l3">
    <span class="lbl">City/ Town/Village*:</span><input class="cb grow" style="--n:14;max-width:180px" maxlength="14" name="a1_city">
  </div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">District*:</span><input class="cb" style="--n:26" maxlength="26" name="a1_dist">
    <span class="lbl">Pin/Post Code*:</span><input class="cb" style="--n:8" maxlength="8" name="a1_pin">
  </div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">State / UT Name*:</span><input class="cb" style="--n:26" maxlength="26" name="a1_state">
    <div class="col"><span class="lbl">Country Code*:</span><span class="note">(ISO3166)</span></div><input class="cb" style="--n:3" maxlength="3" name="a1_country">
  </div>

  <div class="lbl mt4">4.2 Correspondence / Local Address Details *</div>
  <div class="row"><input type="checkbox" class="ck big" name="a2_same"><span class="small">SAME AS CURRENT / PERMANENT ADDRESS DETAILS (IN CASE OF MULTIPLE CORRESPONDENCE / LOCAL ADDRESSES, PLEASE FILL 'ANNEXURE III')</span></div>
  <div class="row">
    <span class="lbl">Address Type*:</span>
    <span><input type="checkbox" class="ck" name="a2_t_resbiz"><span class="small">RESIDENTIAL / BUSINESS</span></span>
    <span><input type="checkbox" class="ck" name="a2_t_res"><span class="small">RESIDENTIAL</span></span>
    <span><input type="checkbox" class="ck" name="a2_t_biz"><span class="small">BUSINESS</span></span>
    <span><input type="checkbox" class="ck" name="a2_t_reg"><span class="small">REGISTERED OFFICE</span></span>
    <span><input type="checkbox" class="ck" name="a2_t_unspec"><span class="small">UNSPECIFIED</span></span>
  </div>
  <div class="row">
    <span class="lbl">Proof of Address* :</span>
    <span><input type="checkbox" class="ck" name="a2_poa_cert"><span class="small">CERTIFICATE OF INCORPORATION / FORMATION</span></span>
    <span><input type="checkbox" class="ck" name="a2_poa_reg"><span class="small">REGISTRATION CERTIFICATE</span></span>
  </div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 1*:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="a2_l1"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 2:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="a2_l2"></div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">Line 3:</span><input class="cb" style="--n:26" maxlength="26" name="a2_l3">
    <span class="lbl">City/ Town/Village*:</span><input class="cb grow" style="--n:14;max-width:180px" maxlength="14" name="a2_city">
  </div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">District*:</span><input class="cb" style="--n:26" maxlength="26" name="a2_dist">
    <span class="lbl">Pin/Post Code*:</span><input class="cb" style="--n:8" maxlength="8" name="a2_pin">
  </div>
  <div class="row nowrap">
    <span class="lbl" style="width:64px">State / UT Name*:</span><input class="cb" style="--n:26" maxlength="26" name="a2_state">
    <div class="col"><span class="lbl">Country Code*:</span><span class="note">(ISO3166)</span></div><input class="cb" style="--n:3" maxlength="3" name="a2_country">
  </div>

  <div class="pgnum">1</div>
</div>
