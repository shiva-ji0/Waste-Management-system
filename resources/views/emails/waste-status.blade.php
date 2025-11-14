<h2>Hello {{ $waste->user->name }}</h2>
<p>Your waste pickup status has been updated to: <strong>{{ ucfirst($waste->status) }}</strong></p>
<p>Waste Type: {{ $waste->waste_type }}</p>
@if($waste->status!=="rejected" || $waste->status!=="pending")
<p>Date: {{ $waste->date }} | Shift: {{ $waste->shift }}</p>
@endif
<p>Thank you for using our service!</p>
