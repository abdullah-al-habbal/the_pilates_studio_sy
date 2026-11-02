# Layer model

`Action -> Handler -> Service -> Repository -> Model`

- **Action** (`app/Http/Actions/Web/...`, `app/Actions/...`): single-invocation
  controller. Parses the request, builds a Command/DTO, calls one Handler.
- **Command / DTO** (`app/Commands`, `app/DTOs`, `app/Data`): input carriers.
- **Handler** (`app/Handlers`): business logic, `readonly` when stateless.
- **Validator services** (e.g. `RefundValidatorService`): enforce domain rules
  before state changes; complex rules use an explicit `assert*` method.
- **Service** (`app/Services`): domain services, grouped per entity.
- **Repository** (`app/Repositories/Eloquent`, base `BaseEloquentRepository`): persistence.
- Validation: prefer FormRequest.
