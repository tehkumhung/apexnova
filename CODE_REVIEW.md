# Stage 2 - Code Review Feedback

## Blocking issues

1. `Order::all()` loads every order into memory. Paginate and use an explicit, stable sort order.
2. `User::find()` inside the loop creates N+1 queries. Define `Order::user()` and eager-load it. The current code also crashes when a user is missing.
3. `store()` accepts unvalidated input. Use a Form Request with authorization and rules. Derive `user_id` from the authenticated user where appropriate, and do not let clients freely set protected workflow fields such as status.
4. The audit query concatenates data into SQL, bypasses Eloquent, and discards its result. Use a relationship or bindings. If the audit operation is part of creation, wrap both writes in a transaction.
5. `destory()` is misspelled and will not match a resource route. Rename it to `destroy()`.
6. `Order::find($id)` can return `null`. Prefer route-model binding or `findOrFail()`.
7. Authentication and per-order authorization are missing. Add auth middleware and policies.

## Design and API improvements

- Use API Resources instead of manual arrays for consistent, null-safe responses.
- Return `201 Created` from `store()` and `204 No Content` from `destroy()`.
- Store money as fixed-precision decimal and validate it as non-negative.
- Consider soft deletes and explicit order-state transitions for auditability.
- Add filters, bounded pagination, indexes, and tests for validation, authorization, not-found behavior, N+1 regressions, and deletion.
- Remove unused imports and the unused `Request` parameter from `index()` unless filters are added.

## Example direction

```php
public function index(OrderIndexRequest $request): AnonymousResourceCollection
{
    $orders = Order::query()
        ->with('user:id,name')
        ->filter($request->validated())
        ->latest('id')
        ->paginate(20);

    return OrderResource::collection($orders);
}

public function store(StoreOrderRequest $request): OrderResource
{
    $order = DB::transaction(function () use ($request) {
        $order = $request->user()->orders()->create($request->safe()->except('status'));
        $order->auditLogs()->create(['event' => 'created']);

        return $order;
    });

    return new OrderResource($order->load('user'));
}

public function destroy(Order $order): Response
{
    $this->authorize('delete', $order);
    $order->delete();

    return response()->noContent();
}
```
