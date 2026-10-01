{{-- FORM PAGE 17 : ANNEXURE-VI (Country of residence / FATCA & CRS box) --}}
<div class="fpage">
  <img class="logo" src="{{ asset('images/forms/pnb-logo.png') }}" alt="Punjab National Bank" style="width:280px">
  <div class="bar"><span>Country of Residence as per Tax Laws</span><span>Annexure-VI</span></div>
  <div class="bordered">
    <div class="row nowrap">
      <span class="lbl">Applicant CIF No.:</span><input class="cb" style="--n:13" maxlength="13" name="vi_cif">
      <span class="lbl center grow">For office use only</span>
      <span class="lbl">Date:</span><input class="cb" style="--n:8" maxlength="8" name="vi_date">
    </div>
    <div class="row nowrap"><span class="lbl">Entity name :</span><input class="cb" style="--n:13" maxlength="13" name="vi_entity"></div>
    <div class="row nowrap"><span class="lbl">Entity account number</span><input class="cb" style="--n:19" maxlength="19" name="vi_ac"></div>
  </div>

  <div class="bordered mt2" style="font-size:8px">
    <div class="lbl" style="text-align:right">FATCA &amp; CRS Box</div>
    <div class="row nowrap">
      <span class="lbl">Tax Resident of US:</span>
      <span class="lbl">YES</span><input type="checkbox" class="ck big" name="us_y">
      <span class="lbl">NO</span><input type="checkbox" class="ck big" name="us_n">
      <span class="small">(IF '<b>YES</b>' , PLEASE PROVIDE US TIN)</span>
      <span class="lbl">US TIN:</span><input class="cb grow" style="--n:15" maxlength="15" name="us_tin">
    </div>
    <div class="row nowrap mt2"><span class="lbl">If tax resident of US, whether the person is</span></div>
    <div class="row nowrap">
      <span class="lbl">A US Person</span>
      <span class="lbl">YES</span><input type="checkbox" class="ck" name="usperson_y">
      <span class="lbl">NO</span><input type="checkbox" class="ck" name="usperson_n">
      <span class="note">(A Tax resident of US is US person, see Instruction 'J' )</span>
    </div>
    <div class="row nowrap">
      <span class="lbl">A Specified US Person (see Instructions 'K' )</span>
      <span class="lbl">YES</span><input type="checkbox" class="ck" name="sus_y">
      <span class="lbl">NO</span><input type="checkbox" class="ck" name="sus_n">
      <span class="note">(If 'Specified US Person' is YES , then the entity is US reportable)</span>
    </div>
    <div class="row nowrap mt2">
      <span class="lbl">Tax Resident Outside India other than US:</span>
      <span class="lbl">YES</span><input type="checkbox" class="ck big" name="troi_y">
      <span class="lbl">NO</span><input type="checkbox" class="ck big" name="troi_n">
    </div>
    <div class="row nowrap">
      <span class="small">If 'Yes' , Please provide Country code</span><input class="cb" style="--n:4" maxlength="4" name="troi_cc">
      <span class="small">&amp; TIN / Functional Equivalent:</span><input class="cb grow" style="--n:15" maxlength="15" name="troi_tin">
    </div>
    <div class="bordered mt2" style="font-size:7.4px">
      IF TAX RESIDENT OUTSIDE INDIA OTHER THAN US IS "YES" , WHETHER ENTITY FALLS IN ANY OF THE FOLLOWING CATEGORY (TICK FROM THE FOLLOWING CATEGORY AS APPLICABLE - IF NONE OF THE FOLLOWING CATEGORY IS MARKED "YES" THEN THE ACCOUNT IS AN "OTHER REPORTABLE ACCOUNT" )
    </div>
    <div class="flex mt2">
      <div style="flex:1.4">
        <div class="row nowrap"><span class="small grow">I.&nbsp;&nbsp;Any corporation the stock of which is regularly traded on one or more established securities market</span><span class="lbl">YES</span><input type="checkbox" class="ck" name="cat1_y"><span class="lbl">NO</span><input type="checkbox" class="ck" name="cat1_n"></div>
        <div class="row nowrap"><span class="small grow">II.&nbsp;Any corporation that is a related entity of a corporation described in (i) above</span><span class="lbl">YES</span><input type="checkbox" class="ck" name="cat2_y"><span class="lbl">NO</span><input type="checkbox" class="ck" name="cat2_n"></div>
        <div class="row nowrap"><span class="small grow">III.&nbsp;A Governmental Entity</span><span class="lbl">YES</span><input type="checkbox" class="ck" name="cat3_y"><span class="lbl">NO</span><input type="checkbox" class="ck" name="cat3_n"></div>
        <div class="row nowrap"><span class="small grow">IV.&nbsp;An International Organization</span><span class="lbl">YES</span><input type="checkbox" class="ck" name="cat4_y"><span class="lbl">NO</span><input type="checkbox" class="ck" name="cat4_n"></div>
        <div class="row nowrap"><span class="small grow">V.&nbsp;A Central Bank</span><span class="lbl">YES</span><input type="checkbox" class="ck" name="cat5_y"><span class="lbl">NO</span><input type="checkbox" class="ck" name="cat5_n"></div>
        <div class="row nowrap"><span class="small grow">VI.&nbsp;A Financial Institution</span><span class="lbl">YES</span><input type="checkbox" class="ck" name="cat6_y"><span class="lbl">NO</span><input type="checkbox" class="ck" name="cat6_n"></div>
      </div>
      <div class="col" style="flex:1;gap:6px">
        <div class="ibox">IF ANY OF THE ITEM (I) TO (VI) IS TICKED 'YES'THE ACCOUNT IS NOT AN "OTHER REPORTABLE ACCOUNT"</div>
        <div class="ibox">IF ENTITY IS NEITHER A TAX RESIDENT OF INDIA OR US NOR A TAX RESIDENT OUTSIDE INDIA OTHER THAN US, THEN THE FIELD <b>NO RESIDENCE FOR TAX PURPOSE</b> WILL BE 'YES'</div>
      </div>
    </div>
    <div class="row nowrap mt2">
      <span class="lbl">No residence for tax purpose</span>
      <span class="lbl">YES</span><input type="checkbox" class="ck" name="nores_y">
      <span class="lbl">NO</span><input type="checkbox" class="ck" name="nores_n">
    </div>
    <div class="row nowrap">
      <span class="small">If 'YES' Please provide , Country Code where the principal office of the entity located</span>
      <span class="lbl">Country Code</span><input class="cb" style="--n:6" maxlength="6" name="nores_cc">
    </div>
    <div class="row nowrap mt2">
      <span class="lbl">Multiple Tax Residency*:</span>
      <span class="lbl">YES</span><input type="checkbox" class="ck" name="mtr_y">
      <span class="lbl">NO</span><input type="checkbox" class="ck" name="mtr_n">
      <span class="note">(If '<b>YES</b>', please fill the table below)</span>
    </div>
    <div class="small mt2">1. If an entity is a<br>
    <span style="margin-left:60px">Specified US Person and also has a Tax residency outside India other than US , the entity has multiple tax residency.</span><br>
    2. If it is not a Specified US Person but has Tax residencies outside India other than US in more than one country the entity has multiple tax residency.</div>

    @for($t=0;$t<2;$t++)
    <table class="tbl mt2">
      <tr>
        <th style="width:30%">Country of Tax Residence outside India other than US</th>
        <th style="width:35%">Tax Identification Number or equivalent , if issued by jurisdiction</th>
        <th>Identification Type (TIN, Company Identification Number (CIN) , EIN or Other, please specify)</th>
      </tr>
      <tr><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td></tr>
      <tr>
        <td colspan="3" style="padding:0">
          <table style="width:100%;border-collapse:collapse;font-size:8px">
            <tr><td colspan="2" style="border:0;padding:2px 4px"><b>Address*</b></td></tr>
            <tr>
              <td style="border:0;padding:2px 4px;width:65%">
                <div class="row nowrap"><span class="lbl" style="width:44px">Line 1:</span><input class="cb grow" style="--n:28;width:100%" maxlength="28" name="mtr{{$t}}_l1"></div>
                <div class="row nowrap"><span class="lbl" style="width:44px">Line 2:</span><input class="cb grow" style="--n:28;width:100%" maxlength="28" name="mtr{{$t}}_l2"></div>
                <div class="row nowrap"><span class="lbl" style="width:44px">Line 3:</span><input class="cb grow" style="--n:28;width:100%" maxlength="28" name="mtr{{$t}}_l3"></div>
              </td>
              <td style="border:0;padding:2px 4px">
                <div class="row nowrap"><span class="lbl">City :</span><input class="cb" style="--n:13" maxlength="13" name="mtr{{$t}}_city"></div>
                <div class="row nowrap"><span class="lbl">State :</span><input class="cb" style="--n:13" maxlength="13" name="mtr{{$t}}_state"></div>
                <div class="row nowrap"><span class="lbl">Pin :</span><input class="cb" style="--n:9" maxlength="9" name="mtr{{$t}}_pin"></div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
    @endfor

    <div class="col mt4" style="align-items:flex-end">
      <div class="bordered" style="width:240px;height:60px;display:flex;align-items:flex-end;justify-content:center"><b>Signature of Applicant/s</b></div>
    </div>
  </div>

  <div class="pgnum">17</div>
</div>
