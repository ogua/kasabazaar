{{-- Optional SMS consent for the website forms; stored as sms_opt_in. Wording matches the SMS Terms page. --}}
<div class="form-check text-start small">
   <input class="form-check-input" type="checkbox" name="sms_opt_in" value="1" id="{{ $id ?? 'sms_opt_in' }}">
   <label class="form-check-label" for="{{ $id ?? 'sms_opt_in' }}">
      Text me updates about my request and shipment at the number above. Msg frequency varies
      (typically 3&ndash;10 per shipment); msg &amp; data rates may apply. Reply STOP to opt out, HELP for help.
      Optional &mdash; see our <a href="{{ route('sms-terms') }}">SMS Terms</a> and
      <a href="{{ route('privacy-policy') }}">Privacy Policy</a>.
   </label>
</div>
