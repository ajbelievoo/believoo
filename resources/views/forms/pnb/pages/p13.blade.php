{{-- FORM PAGE 13 : ANNEXURE-II part 2 (PAN, PoI/address, OVD, contact, tax residency, outside address) --}}
<div class="fpage">
  <div class="row nowrap">
    <span class="lbl">PAN /Tax Identification Number or equivalent*:</span><input class="cb" style="--n:17" maxlength="17" name="cp_pan">
    <span class="note" style="max-width:190px">(If jurisdiction of residence for tax purpose' is India only , the PAN in this field )</span>
  </div>
  <div class="row nowrap">
    <span class="lbl">Place / City of birth :</span><input class="cb" style="--n:16" maxlength="16" name="cp_pob">
    <div class="col"><span class="lbl">Country code of birth :</span><span class="note">(ISO 3166)</span></div><input class="cb" style="--n:6" maxlength="6" name="cp_cob">
  </div>

  <div class="bar"><span>3. Proof of Identity/Address (Please tick the appropriate box (any one ID type) and give details)*</span></div>
  <div class="row">
    <span><input type="checkbox" class="ck big" name="poi_a"><span class="small">A- Passport</span></span>
    <span><input type="checkbox" class="ck big" name="poi_b"><span class="small">B- Voter ID Card</span></span>
    <span><input type="checkbox" class="ck big" name="poi_c"><span class="small">C- Driving Licence</span></span>
    <span><input type="checkbox" class="ck big" name="poi_d"><span class="small">D- NREGA Job Card</span></span>
  </div>
  <div class="row">
    <span><input type="checkbox" class="ck big" name="poi_e"><span class="small">E- Letter issued by National Population Register containing details of name and address</span></span>
    <span><input type="checkbox" class="ck big" name="poi_f"><span class="small">Proof of possession of Aadhar No.</span></span>
  </div>
  <div class="row nowrap">
    <span class="lbl">Document No. / Identification Number*</span><input class="cb" style="--n:22" maxlength="22" name="poi_doc">
    <span class="lbl">Issued Date:</span><input class="cb" style="--n:8" maxlength="8" name="poi_isd">
    <span class="lbl">Date of Expiry :</span><input class="cb" style="--n:8" maxlength="8" name="poi_exp">
  </div>
  <div class="row nowrap">
    <span class="lbl">Issued By*:</span><input class="cb" style="--n:25" maxlength="25" name="poi_issuer">
    <span class="lbl">Issued At*:</span><input class="cb grow" style="--n:25" maxlength="25" name="poi_issuedat">
  </div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 1*:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="poi_l1"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 2*:</span><input class="cb" style="--n:30" maxlength="30" name="poi_l2"><span class="lbl">City / Town / Village *:</span><input class="cb grow" style="--n:14;max-width:180px" maxlength="14" name="poi_city"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">District*:</span><input class="cb" style="--n:30" maxlength="30" name="poi_dist"><span class="lbl">Pin / Post Code*:</span><input class="cb" style="--n:8" maxlength="8" name="poi_pin"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">State / UT Name Code*:</span><input class="cb" style="--n:28" maxlength="28" name="poi_state"><div class="col"><span class="lbl">Country Code*:</span><span class="note">(ISO 3166 )</span></div><input class="cb" style="--n:8" maxlength="8" name="poi_country"></div>

  <div class="bar"><span>4. If the proof of Address/OVD provided does not contain current address, please provide any of the documents below as OVD (Officially Valid Document)</span></div>
  <div class="row">
    <span><input type="checkbox" class="ck big" name="ovd1"><span class="small">1. Utility bill</span></span>
    <span><input type="checkbox" class="ck big" name="ovd2"><span class="small">2. PPO/FPPO</span></span>
    <span><input type="checkbox" class="ck big" name="ovd3"><span class="small">3. Property or Municipal Tax Receipt</span></span>
  </div>
  <div class="row">
    <input type="checkbox" class="ck big" name="ovd4"><span class="small" style="flex:1">4. Letter of allotment of accommodation issued by employer/issued by State or Central Government Departments, Statutory or Regulatory Bodies, Public Sector Undertaking Scheduled Commercial Banks, Financial Institutions and Listed Companies. Similarly, Lease and License agreements with such employers allotting official accommodation."</span>
  </div>
  <div class="row">
    <input type="checkbox" class="ck big" name="ovd5"><span class="small" style="flex:1">5. Self-Declaration (applicable only when customer has carried out e-KYC (Aadhaar authentication) and address in Aadhaar is not same as current address</span>
  </div>
  <div class="small" style="font-size:7px">I/WE SHALL SUBMIT OVD WITH UPDATED CURRENT ADDRESS WITHIN A PERIOD OF THREE MONTHS, FAILING WHICH BANK MAY RESTRICT THE OPERATIONS IN THE ACCOUNT (NOT APPLICABLE WHEN SELF DECLARATION IS PROVIDED BY THE CUSTOMER) (AS PER POINT NO. 5 ABOVE)</div>
  <div class="row nowrap mt2">
    <span class="lbl">Document No. / Identification Number*</span><input class="cb" style="--n:22" maxlength="22" name="ovd_doc">
    <span class="lbl">Issued Date:</span><input class="cb" style="--n:8" maxlength="8" name="ovd_isd">
    <span class="lbl">Date of Expiry :</span><input class="cb" style="--n:8" maxlength="8" name="ovd_exp">
  </div>
  <div class="row nowrap">
    <span class="lbl">Issued By*:</span><input class="cb" style="--n:25" maxlength="25" name="ovd_issuer">
    <span class="lbl">Issued At*:</span><input class="cb grow" style="--n:25" maxlength="25" name="ovd_issuedat">
  </div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 1*:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="ovd_l1"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 2*:</span><input class="cb" style="--n:30" maxlength="30" name="ovd_l2"><span class="lbl">City / Town / Village *:</span><input class="cb grow" style="--n:14;max-width:180px" maxlength="14" name="ovd_city"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">District*:</span><input class="cb" style="--n:30" maxlength="30" name="ovd_dist"><span class="lbl">Pin / Post Code*:</span><input class="cb" style="--n:8" maxlength="8" name="ovd_pin"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">State / UT Name Code*:</span><input class="cb" style="--n:28" maxlength="28" name="ovd_state"><div class="col"><span class="lbl">Country Code*:</span><span class="note">(ISO 3166 )</span></div><input class="cb" style="--n:8" maxlength="8" name="ovd_country"></div>

  <div class="bar"><span>5. Contact Details<span class="sub"> (All communications will be sent on provided Mobile no./ Email- ID) (Please refer Instruction 'F' at the end)</span></span></div>
  <div class="row nowrap">
    <span class="lbl">Tel. (Off):</span><input class="cb" style="--n:4" maxlength="4"><input class="cb" style="--n:8" maxlength="8">
    <span class="grow"></span>
    <span class="lbl">Tel. (Res):</span><input class="cb" style="--n:4" maxlength="4"><input class="cb" style="--n:8" maxlength="8">
  </div>
  <div class="row nowrap"><span class="lbl">Fax:</span><input class="cb" style="--n:4" maxlength="4"><input class="cb" style="--n:8" maxlength="8"></div>
  <div class="row nowrap">
    <span class="lbl">Mobile 1:</span><input class="cb" style="--n:10" maxlength="10">
    <span class="grow"></span><span class="lbl">Mobile 2:</span><input class="cb" style="--n:10" maxlength="10">
  </div>
  <div class="row nowrap"><span class="lbl">Email ID 1:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52"></div>
  <div class="row nowrap"><span class="lbl">Email ID 2:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52"></div>

  <div class="bar"><span>6. Multiple Tax Residency:<span class="sub"> Details of Country of Tax Residence (In addition to India) in US and/or in any other Country or Territory Outside India as Under:</span></span></div>
  <table class="tbl">
    <tr><th>Country of Tax Residence #</th><th>Tax Identification Number or equivalent, if issued by jurisdiction</th><th>Identification Type (TIN or Other, please specify)</th></tr>
    <tr><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td></tr>
    <tr><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td></tr>
    <tr><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td></tr>
  </table>
  <div class="note mt2"># In case, country of tax residence is India, PAN is treated as TIN.<br>
  1. A citizen of US including individual born in US but resident in another country (who has not given up US citizenship).<br>
  2. A person residing in US including US green card holder.<br>
  3. Certain persons who spend more than 180 days in US each year.</div>

  <div class="bar"><span>7. Address in outside jurisdiction/Country - where the applicant is resident outside India for tax purposes</span></div>
  <div class="row">
    <span class="lbl">Address Type*</span>
    <input type="checkbox" class="ck big" name="oj_t1"><span class="small">Residential / Business</span>
    <input type="checkbox" class="ck big" name="oj_t2"><span class="small">Residential</span>
    <input type="checkbox" class="ck big" name="oj_t3"><span class="small">Business</span>
    <input type="checkbox" class="ck big" name="oj_t4"><span class="small">Registered Office</span>
    <input type="checkbox" class="ck big" name="oj_t5"><span class="small">Unspecified</span>
  </div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 1*:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="oj_l1"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 2*:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="oj_l2"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">Line 3:</span><input class="cb" style="--n:30" maxlength="30" name="oj_l3"><span class="lbl">City / Town / Village*:</span><input class="cb grow" style="--n:14;max-width:180px" maxlength="14" name="oj_city"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">District*:</span><input class="cb" style="--n:30" maxlength="30" name="oj_dist"><span class="lbl">Pin / Post Code*:</span><input class="cb" style="--n:8" maxlength="8" name="oj_pin"></div>
  <div class="row nowrap"><span class="lbl" style="width:64px">State / UT Name Code*:</span><input class="cb" style="--n:14" maxlength="14" name="oj_state"><div class="col"><span class="lbl">Country Code*:</span><span class="note">(ISO 3166 )</span></div><input class="cb" style="--n:8" maxlength="8" name="oj_country"></div>

  <div class="pgnum">13</div>
</div>
