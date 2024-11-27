Email: {{$email??null}} <br>
@if($user->role_id == 3)
    You just signed up as an orphan sponsor in Tarahum app. <br>
    @elseif ($user->role_id == 2)
    You just signed up as an orphan guardian in Tarahum app. <br>
@endif
Here is your password: <br>
{{$password??null}}
<br><br>
You can change it under Settings once you log in <br><br>
Best,<br>
Tarahum team