<x-mail::message>
  # Introduction
  <h2>Welcome {{ $data->name }} 👋</h2>
  <p>Your account has been successfully created.</p>


  <x-mail::button :url="''">
    Button Text
  </x-mail::button>

  Thanks,<br>
  {{ config('app.name') }}
</x-mail::message>