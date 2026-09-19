<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="@yield('description', 'موثوق يساعدك في العثور على مراكز صيانة سيارات موثقة بالقرب منك.')">

        <title>@yield('title', 'موثوق | مركز الصيانة المناسب لسيارتك')</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen">
        @include('partials.header')

        <main>
            @yield('content')
        </main>

        @include('partials.footer')
    </body>
</html>
