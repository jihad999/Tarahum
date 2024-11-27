Email: {{$email??null}} <br>
@if($user->role_id == 3)
    You just signed up as a Sponsor in Tarahum app.  <br>
@elseif ($user->role_id == 2)
    You just signed up as a Guardian in Tarahum app.  <br>
@endif
Here is your verification code/OTP: <br>
{{$code??null}}
<br><br>
Best,<br>
Tarahum team