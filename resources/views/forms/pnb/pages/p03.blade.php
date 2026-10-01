{{-- FORM PAGE 3 : 12 other entity details, 13 country of residence, 14 Form 60, 15 nomination --}}
<div class="fpage">
  <div class="bar"><span>12. Other Entity Details:</span></div>
  <div class="small">DETERMINE* WHETHER THE ENTITY IS 'FI' <b>OR</b> 'NFE' (AN ENTITY CAN BE EITHER AN 'FI' OR 'NFE' , IT CAN NOT BE BOTH)</div>
  <div class="row nowrap mt2">
    <input type="checkbox" class="ck big" name="ent_fi">
    <span class="small"><b>FINANCIAL INSTITUTION (FI)</b> : (IF FINANCIAL INSTITUTION (FI) IS TICKED , PLEASE ALSO FILL <b>ANNEXURE I &amp; ANNEXURE II</b> FOR ALL THE RELATED PERSON) <b>OR</b></span>
  </div>
  <div class="row nowrap mt2">
    <input type="checkbox" class="ck big" name="ent_nfe">
    <span class="small"><b>NON FINANCIAL ENTITY (NFE)</b> : IF ENTITY IS NFE, WETHER IT IS*:</span>
    <input class="cb" style="--n:1" maxlength="1" name="nfe_active"><span class="lbl">ACTIVE NFE OR</span>
    <input class="cb" style="--n:1" maxlength="1" name="nfe_passive"><span class="lbl">PASSIVE NFE</span>
  </div>
  <div class="note">(AN ENTITY CAN BE EITHER AN 'ACTIVE NFE' OR 'PASSIVE NFE' , IT CAN NOT BE BOTH - SEE INSTRUCTIONS 'H' IN GENERAL GUIDELINES FOR ACTIVE &amp; PASSIVE NFE)</div>
  <div class="row nowrap mt2">
    <span class="lbl">Number of controlling person(s):</span><input class="cb" style="--n:2" maxlength="2" name="num_cp">
    <span class="note">(APPLICABLE ONLY IN CASE OF PASSIVE NFE, FILL <b>ANNEXURE II</b> FOR EACH CONTROLLING PERSON)</span>
  </div>
  <div class="row nowrap mt2">
    <span class="lbl">Direct reporting non financial foreign entity (NFFE):</span>
    <input class="cb" style="--n:1" maxlength="1" name="dr_nffe_y"><span class="lbl">YES</span>
    <input class="cb" style="--n:1" maxlength="1" name="dr_nffe_n"><span class="lbl">NO</span>
  </div>
  <div class="row nowrap">
    <span class="lbl">If Yes please provide GIIN of direct reporting NFFE:</span><input class="cb grow" style="--n:19" maxlength="19" name="giin_nffe">
  </div>
  <div class="row nowrap">
    <div class="col"><span class="lbl">Legal Entity Identifier (L.E.I Code. No.):</span><span class="note">(AS &amp; WHEN APPLICABLE)</span></div>
    <input class="cb grow" style="--n:20" maxlength="20" name="lei_code">
  </div>

  <div class="bar"><span>13. Country of Residence as per Tax Laws*</span></div>
  <div class="row nowrap">
    <span class="lbl">Tax resident of India only and not of any other country outside India</span>
    <span class="lbl">YES</span><input class="cb" style="--n:1" maxlength="1" name="tax_ind_y">
    <span class="lbl">NO</span><input class="cb" style="--n:1" maxlength="1" name="tax_ind_n">
    <span class="note">(IF NO, PLEASE FILL THE DETAILS ANNEXURE -VI)</span>
  </div>

  <div class="bar center" style="flex-direction:column;align-items:center">
    <span><span style="float:left">14</span> Income-tax Rules, 1962<br>FORM 60 [see second proviso to rule 114B]</span>
    <span class="sub">FORM 60 ONLY FOR ENTITIES OTHER THAN COMPANIES AND PARTNERSHIPS (In Case PAN is not Available)</span>
  </div>
  <div class="row nowrap"><span class="lbl">Name:</span><input class="cb grow" style="--n:52;width:100%" maxlength="52" name="f60_name"></div>
  <div class="row nowrap"><span class="note">(SAME AS ID PROOF)</span><input class="ln grow" style="border-bottom-style:dotted"></div>
  <div class="row nowrap mt2">
    <span class="small">IF APPLIED FOR PAN AND IT IS NOT YET GENERATED, ENTER DATE OF APPLICATION</span>
    <input class="cb" style="--n:8" maxlength="8" name="f60_pan_appl_date">
    <span class="lbl">&amp; the acknowledgement number</span><input class="cb" style="--n:9" maxlength="9" name="f60_ack">
  </div>
  <div class="row nowrap mt2">
    <span class="small grow">IF PAN IS NOT APPLIED , FILL ESTIMATED TOTAL INCOME (INCLUDING INCOME OF SPOUSE, MINOR CHILD, ETC) AS PER SECTION 64 OF INCOME TAX ACT 1961 FOR THE FINANCIAL YEAR IN WHICH THE ABOVE TRANSACTION IS HELD</span>
    <input class="ln" style="width:60px">
  </div>
  <div class="row nowrap mt2">
    <span class="lbl">Agriculture income (Rs)</span><input class="cb" style="--n:13" maxlength="13" name="f60_agri">
    <span class="lbl">Other than agricultural income</span><input class="cb" style="--n:15" maxlength="15" name="f60_other">
  </div>
  <div class="center mt2" style="font-weight:700;text-decoration:underline">VERIFICATION</div>
  <div class="small mt2" style="text-align:justify">
    I <input class="ln" style="width:200px;border-bottom-style:dotted" name="f60_decl_name"> do hereby declare that what is stated above is true to the best
    of my knowledge and belief. I further declare I do not have a permanent account number and my/our estimated total income (including income of spouse, minor child, etc.) as per section 64 of
    Income Tax Act 1961 computed in accordance with the provisions of Income Tax Act 1961 for the financial year in which the above transaction is held will be less than maximum amount not
    chargeable to tax.
  </div>
  <div class="row nowrap mt2">
    <span class="small">Verified today, the</span><input class="ln" style="width:90px;border-bottom-style:dotted" name="f60_vday"><span class="small">day of</span>
    <input class="ln" style="width:110px;border-bottom-style:dotted" name="f60_vmonth"><span class="small">20</span><input class="ln" style="width:40px;border-bottom-style:dotted" name="f60_vyear"><span class="small">.</span>
  </div>
  <div class="row nowrap">
    <span class="small">Place:</span><input class="ln" style="width:150px;border-bottom-style:dotted" name="f60_place">
    <span class="grow"></span><span class="small" style="margin-right:60px">Signature of the Declarant</span>
  </div>

  <div class="bar"><span>15&nbsp;&nbsp;Nomination : Applicable Only For Sole Proprietorship</span></div>
  <div class="row nowrap">
    <input type="checkbox" class="ck big" name="nom_yes">
    <span class="small">I/WE WANT TO MAKE A NOMINATION IN MY/OUR ACCOUNT <b>OR</b></span>
    <input type="checkbox" class="ck big" name="nom_no">
    <span class="small">I/WE DO NOT WANT TO MAKE A NOMINATION IN MY/OUR ACCOUNT<br>(The benefit of nomination facility has been explained to me/us . However I/we don't want to nominate any person in the account)</span>
  </div>
  <div class="bar" style="width:82%"><span>Nomination&nbsp;&nbsp;&nbsp;Form</span></div>
  <div class="small">Nomination under section 45-ZA of the Banking Regulation Act, 1949 and Rule 2 to 4 of Banking Companies (Nomination) Rules, 2025 in respect
  of Bank Deposits.<br>I/we, nominate the following person particulars whereof are given below to whom in the event of my /our death the amount of the deposit in the
  account opened with this AOF may be returned by the bank.</div>
  <div class="lbl mt2">Details of Deposit :</div>
  <div class="row nowrap"><span class="small">Type of Deposit :</span><input class="ln grow" name="dep_type"><span class="lbl">Account No:</span><input class="cb" style="--n:15" maxlength="15" name="dep_ac"></div>
  <div class="small"><b>*For availing multiple nomination facility please fill PNB 1386-Nomination form.<br>*Nomination acknowledgement receipt to be invariably issued to customer</b></div>

  <div class="bar"><span>Details of the Nominee</span></div>
  <div class="row nowrap"><span class="lbl">Name:</span><input class="cb grow" style="--n:44;width:100%" maxlength="44" name="nom_name"></div>
  <div class="row nowrap">
    <span class="lbl">Relationship with the depositor :</span><input class="ln grow" name="nom_rel">
    <span class="lbl">Age:</span><input class="cb" style="--n:2" maxlength="2" name="nom_age">
    <span class="lbl">Date of birth of nominee:</span><input class="cb" style="--n:8" maxlength="8" name="nom_dob">
  </div>
  <div class="row nowrap"><span class="lbl">Address:</span><input class="cb grow" style="--n:44;width:100%" maxlength="44" name="nom_addr1"></div>
  <div class="row nowrap"><input class="cb grow" style="--n:44;width:100%" maxlength="44" name="nom_addr2"></div>
  <div class="row nowrap">
    <span class="lbl">City:</span><input class="cb" style="--n:15" maxlength="15" name="nom_city">
    <span class="lbl">Pin:</span><input class="cb" style="--n:10" maxlength="10" name="nom_pin">
    <span class="lbl">State:</span><input class="cb grow" style="--n:15" maxlength="15" name="nom_state">
  </div>
  <div class="row nowrap">
    <span class="lbl">Mobile number of nominee (Optional)</span><input class="ln grow" name="nom_mob">
    <span class="lbl">CIF No. of nominee :</span><input class="cb" style="--n:8" maxlength="8" name="nom_cif">
  </div>
  <div class="small mt2">As the nominee is a minor on this date, I/We appoint Shri/Smt./Others <input class="ln" style="width:300px" name="nom_guardian"> age <input class="ln" style="width:60px" name="nom_g_age"> years</div>
  <div class="row nowrap"><span class="lbl">Address</span><input class="ln grow" name="nom_g_addr"></div>
  <div class="small">to receive the amount of the deposit on behalf of the nominee in the event of my / our / minor's death during the minority of the nominee.</div>
  <div class="small" style="text-align:right;font-weight:700">Signature / Thumb impression of the Applicant(s)</div>
  <div class="small mt2">Personal Details of Witnesses :( Witnesses are required only in case if applicant is illiterate and is affixing thumb impression)</div>
  <div class="flex mt2">
    <div class="grow">
      <div class="row nowrap"><span class="lbl">Witness 1 Name :</span><input class="ln grow" name="w1_name"></div>
      <div class="row nowrap"><span class="lbl">Address :</span><input class="ln grow" name="w1_addr"></div>
      <div class="small mt2">Signature / Thumb Impression</div>
      <div class="row nowrap mt2"><span class="lbl">Place :</span><input class="ln" style="width:120px" name="w1_place"><span class="lbl">Date :</span><input class="ln" style="width:110px" name="w1_date"></div>
    </div>
    <div class="grow">
      <div class="row nowrap"><span class="lbl">Witness 2 Name :</span><input class="ln grow" name="w2_name"></div>
      <div class="row nowrap"><span class="lbl">Address :</span><input class="ln grow" name="w2_addr"></div>
      <div class="small mt2">Signature / Thumb Impression</div>
      <div class="row nowrap mt2"><span class="lbl">Place :</span><input class="ln" style="width:120px" name="w2_place"><span class="lbl">Date :</span><input class="ln" style="width:110px" name="w2_date"></div>
    </div>
  </div>

  <div class="pgnum">3</div>
</div>
