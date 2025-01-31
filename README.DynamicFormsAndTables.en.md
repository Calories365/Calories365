# Dynamic Forms and Tables in the Frontend

In this project, a **dynamic** architecture for forms and tables in Vue has been implemented.  
All fields and columns are described using configuration objects, allowing new fields/columns to be added without changing the components themselves.

---

## 1. Dynamic Forms

### Concept

Forms are built based on **configs**, located at  
[**`resources/js/src/ComponentConfigs/Form`**](https://github.com/Maaaaxim/bot-panel/tree/main/resources/js/src/ComponentConfigs/Form).

```js
export const admin_rows = [
  {
    label: 'Имя',
    key: 'name',
    type: 'default',
    placeholder: 'введите имя',
    required: true
  },
  {
    label: 'Telegram id',
    key: 'telegram_id',
    type: 'default',
    placeholder: 'введите telegram_id',
    required: true
  },
  {
    label: 'Действия',
    key: 'actions',
    type: 'buttons',
    options: [
      { text: 'Сохранить', button_type: 'default', action: 'submit' },
      { text: 'Удалить',  button_type: 'danger',  action: 'delete' }
    ]
  }
];
```

Each object in the array describes **one form field**:

- `type: 'default'` renders a standard `<input>` (through a special component);
- `type: 'buttons'` creates a block of **dynamic buttons**, where the `options` array defines the texts, styles, and actions for each button.

To **add a new field**, simply add a new object with the necessary properties (`label`, `key`, `type`, etc.) to the config.  
The form component (`BotsForm.vue`) does not need to be changed.

### How it Works Inside `BotsForm.vue`

Inside [`BotsForm.vue`](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsForm.vue), for each config element, a dynamic `<component>` is created:

```html
<template>
  <form ref="formRef" action="#" class="card-body">
    <div v-for="(row, index) in rows" :key="index" class="form-group">
      <label :for="row.key">{{ row.label }}</label>
      <component
        :is="getComponentType(row.type)"
        :name="row.key"
        :data="data[row.key]"
        :options="row.options || {}"
        :required="row.required"
        ...
        @handle="handleEvent"
      />
    </div>
  </form>
</template>
```

- `getComponentType(row.type)` — returns one of the predefined Vue components based on the field type: `default`, `buttons`, `dropdown`, etc.
- `data[row.key]` — the value that will be shown in the field/component if the field is not a group of buttons.
- `:options="row.options"` — passes an array of buttons (or other settings) to the component if it is `type: 'buttons'`.
- `@handle="handleEvent"` — handles events (for example, when a button is clicked) and emits them upwards.

### Example of Using the Form

- [Implementation of the form component for displaying bots (`BotsForm.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsForm.vue)
- [Example of using the form to display a bot (`ShowBot.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/pages/ShowBot.vue)

---

## 2. Dynamic Tables

### Concept

Tables, similar to forms, are built using **configs**, located at  
[**`resources/js/src/ComponentConfigs/Table`**](https://github.com/Maaaaxim/bot-panel/tree/main/resources/js/src/ComponentConfigs/Table).

```js
export const bots_table = [
  { label: 'ID',         key: 'id',       type: 'default', action: null,     limit: 40 },
  { label: 'Юзернейм',   key: 'name',     type: 'link',    action: 'show',    limit: 40 },
  { label: 'Токен',      key: 'token',    type: 'default', action: null,      limit: 100 },
  { label: 'Сообщение',  key: 'message',  type: 'default', action: null,      limit: 40 },
  { label: 'Активный',   key: 'active',   type: 'checkbox',action: null,      limit: 40 },
  { label: 'Удаление',   key: 'delete',   type: 'button',  action: 'delete',  limit: 40 }
];
```

Each object in the array describes **one table column**:

- `key` specifies which field of the object (passed to the table) to take the cell content from.
- `type` specifies how to render the data (as plain text, a link, a checkbox, a button, etc.).
- `action` (if not `null`) defines the event that will be emitted on click/change (e.g., `delete`, `show`).

To **add a new column**, simply add an object with a new `key` to the config. If the data (e.g., `bots`) contains the corresponding field, it will automatically appear in the new column.

### How it Works Inside `BotsTable.vue`

In the [`BotsTable.vue`](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsTable.vue) component, specifically in its "main part" ([`TableMainPart.vue`](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsTable/TableMainPart.vue)), `<td>` cells are created for each row according to the config:

```html
<tr v-for="item in data" :key="item.id">
  <td v-for="column in columns" :key="column.key">
      <component
          :is="getComponentType(column.type)"
          :data="item[column.key]"
          :limit="column.limit"
          :action="column.action"
          :id="item.id"
          @handle="handleEvent"
      >
      </component>
  </td>
</tr>
```

- `getComponentType(column.type)` returns the necessary subcomponent (for example, `TableItemLink`, `TableItemCheckBox`, or `TableItem`).
- `@handle="handleEvent"` emits the event (for example, clicking the "Delete" button) upwards, where the business logic for deletion is handled.

### Example of Using the Table

- [Implementation of the table component (`BotsTable.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsTable.vue)
- [Example of using it to display bots (`ShowBots.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/pages/ShowBots.vue)

---

## 3. Advantages and Conclusions

1. **Modularity**  
   New form fields or table columns are added by editing **only** config files, without changing the components themselves.

2. **Flexibility**  
   Supports different types of fields and cells (text, checkboxes, buttons, lists). For buttons in the form (`type: 'buttons'`), you can pass an array of objects where each object defines the button's text and action — everything is rendered dynamically.

3. **Ease of Maintenance**  
   The display logic and data logic are effectively separated. The component is not "hard-coded" for specific fields — it adapts to the config, and the config clearly describes "what" exactly will be displayed and "how".

Thanks to this structure, the project **scales** without significant changes to the component code and ensures quick adaptation to new requirements.
