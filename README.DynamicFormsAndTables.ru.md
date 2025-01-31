# Динамические формы и таблицы во фронтенде

В этом проекте реализована **динамическая** архитектура для форм и таблиц на Vue.  
Все поля и столбцы описываются при помощи конфигурационных объектов, благодаря чему новые поля/столбцы можно добавлять, не изменяя сами компоненты.

---

## 1. Динамические формы

### Концепция

Формы строятся на основе **конфигов**, расположенных по пути  
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

Каждый объект массива описывает **одно поле** формы:

- `type: 'default'` отрисует стандартный `<input>` (через специальный компонент);
- `type: 'buttons'` создаст блок **динамических кнопок**, где массив `options` определяет тексты, стили и действия для каждой кнопки.

Чтобы **добавить новое поле**, достаточно добавить новый объект с нужными свойствами (`label`, `key`, `type` и т.д.) в конфиг.  
Компонент-форма (`BotsForm.vue`) не придётся менять.

### Как это работает внутри `BotsForm.vue`

Внутри [`BotsForm.vue`](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsForm.vue) для каждого элемента конфига создаётся динамический `<component>`:

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

- `getComponentType(row.type)` — возвращает один из предопределённых Vue-компонентов по типу поля: `default`, `buttons`, `dropdown` и т.д.
- `data[row.key]` — значение, которое будет показано в поле/компоненте, если поле не является группой кнопок.
- `:options="row.options"` — передаёт в компонент массив кнопок (или другие настройки), если это `type: 'buttons'`.
- `@handle="handleEvent"` — обрабатывает события (например, при нажатии на кнопку) и поднимает их «наверх».

### Пример использования формы

- [Реализация компонента формы для вывода ботов (`BotsForm.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsForm.vue)
- [Пример использования формы для вывода бота (`ShowBot.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/pages/ShowBot.vue)

---

## 2. Динамические таблицы

### Концепция

Таблицы аналогично формам строятся при помощи **конфигов**, расположенных по пути  
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

Каждый объект массива описывает **один столбец** таблицы:

- `key` указывает, из какого поля объекта (передаваемого в таблицу) брать содержимое ячейки.
- `type` указывает, как именно рендерить данные (простым текстом, ссылкой, чекбоксом, кнопкой и т.п.).
- `action` (если не `null`) определяет событие, которое будет эмититься при клике/изменении (например, `delete`, `show`).

Чтобы **добавить новый столбец**, достаточно добавить в конфиг объект с новым `key`. Если в данных (например, `bots`) присутствует соответствующее поле, оно автоматически появится в новом столбце.

### Как это работает внутри `BotsTable.vue`

В компоненте [`BotsTable.vue`](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsTable.vue), в частности в его «основной части» ([`TableMainPart.vue`](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsTable/TableMainPart.vue)), для каждой строки создаются ячейки `<td>` согласно конфигу:

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

- `getComponentType(column.type)` возвращает нужный подкомпонент (например, `TableItemLink`, `TableItemCheckBox` или `TableItem`).
- `@handle="handleEvent"` поднимает событие (например, клик по кнопке «Удалить») вверх, где уже решается бизнес-логика удаления.

### Пример использования таблицы

- [Реализация компонента таблицы (`BotsTable.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsTable.vue)
- [Пример использования для вывода ботов (`ShowBots.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/pages/ShowBots.vue)

---

## 3. Преимущества и выводы

1. **Модульность**  
   Новые поля формы или столбцы таблицы добавляются правкой **только** конфиг-файлов, без изменений в самих компонентах.

2. **Гибкость**  
   Поддерживаются разные типы полей и ячеек (текстовые, чекбоксы, кнопки, списки). Для кнопок в форме (`type: 'buttons'`) можно передать массив объектов, где каждый объект определяет текст и действие кнопки — всё рендерится динамически.

3. **Удобство сопровождения**  
   Логика отображения и логика данных фактически разделены. Компонент не «жёстко зашит» под конкретные поля — он подстраивается под конфиг, а конфиг понятно описывает, «что» именно будет показано и «как».

Благодаря такой структуре проект **масштабируется** без существенных изменений кода компонентов и обеспечивает быструю адаптацию к новым требованиям.
