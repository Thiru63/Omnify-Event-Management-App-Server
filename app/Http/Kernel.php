protected $middlewareGroups = [
    'api' => [
        \App\Http\Middleware\LogApiRequests::class, // Add this line
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];