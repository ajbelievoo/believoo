{{-- FORM PAGE 16 : ANNEXURE-V (credit facility undertaking) --}}
<div class="fpage">
  <img class="logo" src="{{ asset('images/forms/pnb-logo.png') }}" alt="Punjab National Bank" style="width:280px">
  <div class="bar"><span>Undertaking: Credit Facility from the Banking System</span><span>Annexure-V</span></div>
  <div class="row nowrap">
    <span class="lbl">Applicant CIF No.:</span><input class="cb" style="--n:20" maxlength="20" name="v_cif">
    <span class="grow"></span><span class="lbl">Date:</span><input class="cb" style="--n:8" maxlength="8" name="v_date">
  </div>
  <div class="row nowrap"><span class="lbl">Cust ID:</span><input class="cb" style="--n:12" maxlength="12" name="v_custid"></div>
  <div class="row nowrap"><span class="lbl">Account Name:</span><input class="cb grow" style="--n:50;width:100%" maxlength="50" name="v_acname"></div>

  <div class="bar"><span>Undertaking : Credit Facility from the Banking System</span></div>
  <div class="row nowrap">
    <input type="checkbox" class="ck big" name="v_avail">
    <span class="small">I/WE AM/ARE AVAILING CREDIT FACILITY(IES) FROM THE BANKING SYSTEM AS DETAILED BELOW (can open CA subject to following provisions):</span>
  </div>
  <table class="tbl mt2">
    <tr><th style="width:40px">SR. NO.</th><th>Name of the Lending Institution(s)</th><th style="width:80px">Branch</th><th>Address of the Branch (with Email and Pin Number)</th><th style="width:90px">Type of Credit Exposure</th><th style="width:80px">Amount in Crores</th></tr>
    @for($i=0;$i<3;$i++)
    <tr><td>{{ $i+1 }}.</td><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td><td><input class="ln" style="width:100%"></td></tr>
    @endfor
  </table>

  <table class="tbl mt2" style="font-size:7.4px;line-height:1.35">
    <tr>
      <td style="width:46%" colspan="2">
        <div class="row nowrap"><b>1.</b><input type="checkbox" class="ck big" name="v_less5"><b>TOTAL CREDIT EXPOSURE LESS THAN RS 5.00 CRORE</b></div>
      </td>
      <td><b>DECLARATION CUM UNDERTAKING</b><br>
      I/WE UNDERTAKE TO INFORM YOU IMMEDIATELY IF AND WHEN THE SUM OF MY/OUR AVAILED CREDIT FACILITY(IES) BECOMES RS.5.00CRORE OR MORE.<br>
      I/WE ALSO UNDERSTAND THAT WHEN THE CREDIT EXPOSURE IS RS. 5.00 CRORE OR MORE, MY/OUR ACCOUNT SHALL BE GOVERNED BY PARA 2<br>
      (I) AND PARA 2 (II) (A) OR (B) AS THE CASE MAY BE</td>
    </tr>
    <tr>
      <td colspan="2">
        <div class="row nowrap"><b>2.</b><input type="checkbox" class="ck big" name="v_more5"><b>TOTAL CREDIT EXPOSURE 5 CRORE OR MORE</b></div>
        <b>WHETHER CUSTOMER IS HAVING CC-OD FACILITY</b>
        <div class="row nowrap mt2"><b>(I)</b><input type="checkbox" class="ck big" name="v_ccod_y"><b>YES</b> (CA CAN BE OPENED SUBJECT TO CONDITIONS GIVEN IN COLUMN 3)</div>
        <div class="row nowrap mt2"><b>(II)</b><input type="checkbox" class="ck big" name="v_ccod_n"><b>NO</b>, IN CASE OF CUSTOMERS WHO HAVE NOT AVAILED CC/OD FACILITY FROM ANY BANK, BANKS MAY OPEN CURRENT ACCOUNT AS UNDER:-</div>
        <div class="mt2"><b>A.</b> BORROWERS WITH EXPOSURE FROM THE BANKING SYSTEM OF RS. 50 CRORE OR MORE<br>
        <b>B.</b> BORROWERS WITH EXPOSURE FROM THE BANKING SYSTEM OF RS. 5 CRORE OR MORE BUT LESS THAN RS. 50 CRORE.</div>
      </td>
      <td>
        1. <b>CURRENT ACCOUNT</b> CAN BE OPENED IN ANY ONE OF THE BANKS (AT THE OPTION OF THE BORROWER) WITH WHICH IT HAS CC/OD FACILITY, PROVIDED THAT THE BANK HAS AT LEAST 10% OF THE EXPOSURE OF THE BANKING SYSTEM TO THAT BORROWER.<br>
        2. OTHER LENDING BANKS MAY OPEN ONLY <b>COLLECTION ACCOUNT</b> SUBJECT TO THE CONDITION THAT FUNDS DEPOSITED IN SUCH COLLECTION ACCOUNTS WILL BE REMITTED WITHIN TWO WORKING DAYS OF RECEIVING SUCH FUNDS, TO THE CC/OD ACCOUNT MAINTAINED WITH THE AFORESAID BANK MAINTAINING CURRENT ACCOUNTS OF THE BORROWER.<br>
        3. <b>NON-LENDING BANKS SHALL NOT OPEN ANY CURRENT ACCOUNT</b> FOR SUCH BORROWERS.<br>
        4. IN CASE NONE OF THE LENDERS HAS AT LEAST 10% EXPOSURE OF THE BANKING SYSTEM TO THE BORROWERS, THE BANK HAVING THE HIGHEST EXPOSURE MAY OPEN CURRENT ACCOUNTS.<br>
        <div class="row nowrap mt2">WHETHER PNB IS LENDER <input type="checkbox" class="ck big" name="v_lender_n"> <b>NO</b> (IF NO, CA CANNOT BE OPENED)</div>
        <div class="row nowrap">WHETHER PNB IS LENDER <input type="checkbox" class="ck big" name="v_lender_y"> <b>YES</b> (CAN OPEN CA SUBJECT TO FOLLOWING CONDITIONS)</div>
        1. A MANDATORY <b>ESCROW MECHANISM</b> WILL BE REQUIRED.<br>
        2. ONLY THE <b>ESCROW MANAGING BANK</b> WILL OPEN THE CURRENT ACCOUNT.<br>
        3. $$ OTHER LENDERS CAN OPEN COLLECTION ACCOUNTS SUBJECT TO THE CONDITION THAT FUNDS WILL BE REMITTED FROM THESE COLLECTION ACCOUNTS TO THE SAID ESCROW ACCOUNT AT THE FREQUENCY AGREED BETWEEN THE BANK AND THE BORROWER.<br>
        4. WHILE THERE IS NO PROHIBITION ON AMOUNT OR NUMBER OF CREDITS IN 'COLLECTION ACCOUNTS', DEBITS IN THESE ACCOUNTS SHALL BE LIMITED TO THE PURPOSE OF REMITTING THE PROCEEDS TO THE SAID ESCROW ACCOUNT<br>
        5. NON-LENDING BANKS SHALL NOT OPEN ANY CURRENT ACCOUNT FOR SUCH BORROWERS.<br>
        <div class="row nowrap mt2">WHETHER PNB IS LENDER <input type="checkbox" class="ck big" name="v_lender_y2"> <b>YES</b> (THERE IS NO RESTRICTION ON OPENING OF CURRENT ACCOUNTS BY THE LENDING BANKS.)</div>
        <div class="row nowrap"><input type="checkbox" class="ck big" name="v_lender_n2"> <b>NO</b> (NON-LENDING BANKS MAY OPEN ONLY COLLECTION ACCOUNTS AS DEFINED ABOVE)</div>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <div class="row nowrap"><b>3.</b><input type="checkbox" class="ck big" name="v_exempt"><b>EXEMPTED ACCOUNTS IRRESPECTIVE OF CREDIT EXPOSURE.</b></div>
      </td>
      <td>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex1"> 1. ACCOUNTS FOR REAL ESTATE PROJECTS MANDATED UNDER SECTION 4(2)I (D) OF THE REAL ESTATE (REGULATION &amp; DEVELOPMENT) ACT 2016 FOR THE PURPOSE OF MAINTAINING 70% OF ADVANCE PAYMENTS COLLECTED FROM THE HOME BUYERS.</div>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex2"> 2. NODAL OR ESCROW ACCOUNTS OF PAYMENT AGGREGATORS/PREPAID PAYMENT INSTRUMENT ISSUERS FOR SPECIFIC ACTIVITIES AS PERMITTED BY DEPARTMENT OF PAYMENTS AND SETTLEMENT SYSTEMS (DPSS), RBI UNDER PAYMENT AND SETTLEMENT SYSTEMS ACT, 2007.</div>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex3"> 3. ACCOUNTS FOR SETTLEMENT OF DUES RELATED TO DEBIT CARD/ATM CARD/CREDIT CARD ISSUERS/ACQUIRERS.</div>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex4"> 4. ACCOUNTS PERMITTED UNDER FEMA, 1999.</div>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex5"> 5. ACCOUNTS FOR THE PURPOSE OF IPO/NFO/FPO/SHARE BUYBACK/DIVIDEND PAYMENT/ISSUANCE OF COMMERCIAL PAPERS/ALLOTMENT OF DEBENTURES/GRATUITY, ETC.</div>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex6"> 6. ACCOUNTS FOR PAYMENT OF TAXES, DUTIES, STATUTORY DUES ETC. OPENED WITH BANKS AUTHORIZED TO COLLECT THE SAME, FOR BORROWERS OF SUCH BANKS WHICH ARE NOT AUTHORIZED TO COLLECT SUCH TAXES, DUTIES, STATUTORY DUES ETC.</div>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex7"> 7. ACCOUNTS OF WHITE LABEL ATM OPERATORS AND THEIR AGENTS FOR SOURCING OF CURRENCY. THE EXEMPTION WILL EXTEND ALSO TO CASH IN TRANSIT (CIT) COMPANIES/CASH REPLENISHMENT AGENCIES (CRAS) SINCE THEY ESSENTIALLY CARRY OUT A SIMILAR ACTIVITY.</div>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex8"> 8. INTER-BANK ACCOUNTS</div>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex9"> 9. ACCOUNTS OF ALL INDIA FINANCIAL INSTITUTIONS (AIFIs) VIZ. EXIM BANK, NABARD, NHB, AND SIDBI</div>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex10"> 10. SPECIFIC ACCOUNTS WHICH ARE STIPULATED UNDER VARIOUS STATUTES AND SPECIFIC INSTRUCTIONS OF OTHER REGULATORS/ REGULATORY DEPARTMENTS/ CENTRAL AND STATE GOVERNMENTS.</div>
        <div class="row nowrap"><input type="checkbox" class="ck" name="v_ex11"> 11. ACCOUNTS ATTACHED BY ORDERS OF CENTRAL OR STATE GOVERNMENTS/ REGULATORY BODY/COURTS/ INVESTIGATING AGENCIES ETC. WHEREIN THE CUSTOMER CANNOT UNDERTAKE ANY DISCRETIONARY DEBITS.</div>
        SCHEME SPECIFIC DOCUMENTS RELATED TO ABOVE CATEGORIES OF ACCOUNTS TO BE SUBMITTED WITH THE AOF.
      </td>
    </tr>
  </table>

  <div class="small mt2">I/WE UNDERTAKE TO INFORM PNB IN CASE OF ANY CHANGES IN THE ABOVE DECLARATION CUM UNDERTAKING REGARDING MY/ OUR CC/OD/ OTHER CREDIT FACILITIES. I/WE ALSO UNDERSTAND THAT IT WILL BE MY/OUR SOLE RESPONSIBILITY TO INFORM PNB REGARDING ANY CHANGES TO THE ABOVE FACTS/ASPECTS STATED BY ME/ US IN THE ABOVE DECLARATION CUM UNDERTAKING. I/WE ALSO AGREE TO PROVIDE FRESH DECLARATION CUM UNDERTAKING IN CASE OF ANY CHANGES TO THE ABOVE FACTS/ASPECTS STATED BY ME / US IN THE ABOVE DECLARATION CUM UNDERTAKING, I/WE ALSO AGREE TO CLOSE THE CURRENT ACCOUNT AS AND WHEN DEMANDED BY PNB. I/WE HEREBY UNDERTAKE TO ABIDE BY THE EXTANT GUIDELINES OF RBI / BANK REGARDING THE OPENING/ MAINTENANCE OF MY/OUR CURRENT ACCOUNT(S).</div>
  <div class="small">PLEASE STRIKE OFF THE INAPPLICABLE OPTION.</div>
  <div class="flex mt2">
    <div>
      <div class="small">$$ I / WE UNDERTAKE THAT FUNDS WILL BE REMITTED FROM THIS COLLECTION ACCOUNT TO ESCROW ACCOUNT AT THE FOLLOWING FREQUENCY:</div>
      <div class="row nowrap mt2">
        <span class="lbl">DAILY</span><input type="checkbox" class="ck" name="freq_d">
        <span class="lbl">WEEKLY</span><input type="checkbox" class="ck" name="freq_w">
        <span class="lbl">MONTHLY</span><input type="checkbox" class="ck" name="freq_m">
        <span class="lbl">OTHERS, PLEASE SPECIFY</span><input class="ln" style="width:130px">
      </div>
    </div>
    <div class="grow"></div>
    <div class="sigbox" style="width:190px;align-self:flex-end">Signature of Applicant/s</div>
  </div>

  <div class="pgnum">16</div>
</div>
