# Динамічні форми та таблиці у фронтенді

У цьому проєкті реалізована **динамічна** архітектура для форм і таблиць на Vue.  
Всі поля та стовпці описуються за допомогою конфігураційних об'єктів, завдяки чому нові поля/стовпці можна додавати, не змінюючи самі компоненти.

---

## 1. Динамічні форми

### Концепція

Форми будуються на основі **конфігів**, розташованих за шляхом  
[**`resources/js/src/ComponentConfigs/Form`**](https://github.com/Maaaaxim/bot-panel/tree/main/resources/js/src/ComponentConfigs/Form).

```js
export const admin_rows = [
  {
    label: 'Ім’я',
    key: 'name',
    type: 'default',
    placeholder: 'введіть ім’я',
    required: true
  },
  {
    label: 'Telegram id',
    key: 'telegram_id',
    type: 'default',
    placeholder: 'введіть telegram_id',
    required: true
  },
  {
    label: 'Дії',
    key: 'actions',
    type: 'buttons',
    options: [
      { text: 'Зберегти', button_type: 'default', action: 'submit' },
      { text: 'Видалити',  button_type: 'danger',  action: 'delete' }
    ]
  }
];
```

Кожен об'єкт масиву описує **одне поле** форми:

- `type: 'default'` відобразить стандартний `<input>` (через спеціальний компонент);
- `type: 'buttons'` створить блок **динамічних кнопок**, де масив `options` визначає тексти, стилі та дії для кожної кнопки.

Щоб **додати нове поле**, достатньо додати новий об'єкт з необхідними властивостями (`label`, `key`, `type` тощо) у конфіг.  
Компонент-форма (`BotsForm.vue`) не потрібно буде змінювати.

### Як це працює всередині `BotsForm.vue`

Всередині [`BotsForm.vue`](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsForm.vue) для кожного елемента конфігу створюється динамічний `<component>`:

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

- `getComponentType(row.type)` — повертає один із попередньо визначених Vue-компонентів за типом поля: `default`, `buttons`, `dropdown` тощо.
- `data[row.key]` — значення, яке буде показано у полі/компоненті, якщо поле не є групою кнопок.
- `:options="row.options"` — передає в компонент масив кнопок (або інші налаштування), якщо це `type: 'buttons'`.
- `@handle="handleEvent"` — обробляє події (наприклад, при натисканні на кнопку) та піднімає їх «вгору».

### Приклад використання форми

- [Реалізація компонента форми для виводу ботів (`BotsForm.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsForm.vue)
- [Приклад використання форми для виводу бота (`ShowBot.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/pages/ShowBot.vue)

---

## 2. Динамічні таблиці

### Концепція

Таблиці аналогічно формам будуються за допомогою **конфігів**, розташованих за шляхом  
[**`resources/js/src/ComponentConfigs/Table`**](https://github.com/Maaaaxim/bot-panel/tree/main/resources/js/src/ComponentConfigs/Table).

```js
export const bots_table = [
  { label: 'ID',         key: 'id',       type: 'default', action: null,     limit: 40 },
  { label: 'Юзернейм',   key: 'name',     type: 'link',    action: 'show',    limit: 40 },
  { label: 'Токен',      key: 'token',    type: 'default', action: null,      limit: 100 },
  { label: 'Повідомлення',  key: 'message',  type: 'default', action: null,      limit: 40 },
  { label: 'Активний',   key: 'active',   type: 'checkbox',action: null,      limit: 40 },
  { label: 'Видалення',   key: 'delete',   type: 'button',  action: 'delete',  limit: 40 }
];
```

Кожен об'єкт масиву описує **один стовпець** таблиці:

- `key` вказує, з якого поля об'єкта (передаваного в таблицю) брати вміст клітинки.
- `type` вказує, як саме рендерити дані (простим текстом, посиланням, чекбоксом, кнопкою тощо).
- `action` (якщо не `null`) визначає подію, яка буде емититись при кліку/зміні (наприклад, `delete`, `show`).

Щоб **додати новий стовпець**, достатньо додати до конфігу об'єкт з новим `key`. Якщо в даних (наприклад, `bots`) присутнє відповідне поле, воно автоматично з’явиться в новому стовпці.

### Як це працює всередині `BotsTable.vue`

У компоненті [`BotsTable.vue`](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsTable.vue), зокрема в його «основній частині» ([`TableMainPart.vue`](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsTable/TableMainPart.vue)), для кожного рядка створюються клітинки `<td>` згідно конфігу:

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

- `getComponentType(column.type)` повертає потрібний підкомпонент (наприклад, `TableItemLink`, `TableItemCheckBox` або `TableItem`).
- `@handle="handleEvent"` піднімає подію (наприклад, клік по кнопці «Видалити») вгору, де вже вирішується бізнес-логіка видалення.

### Приклад використання таблиці

- [Реалізація компонента таблиці (`BotsTable.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/Components/BotsTable.vue)
- [Приклад використання для виводу ботів (`ShowBots.vue`)](https://github.com/Maaaaxim/bot-panel/blob/main/resources/js/src/pages/ShowBots.vue)

---

## 3. Переваги та висновки

1. **Модульність**  
   Нові поля форми або стовпці таблиці додаються шляхом редагування **тільки** конфіг-файлів, без змін у самих компонентах.

2. **Гнучкість**  
   Підтримуються різні типи полів та клітинок (текстові, чекбокси, кнопки, списки). Для кнопок у формі (`type: 'buttons'`) можна передати масив об'єктів, де кожен об'єкт визначає текст та дію кнопки — все рендериться динамічно.

3. **Зручність супроводу**  
   Логіка відображення та логіка даних фактично розділені. Компонент не «жорстко закодований» під конкретні поля — він підлаштовується під конфіг, а конфіг зрозуміло описує, «що» саме буде показано і «як».

Завдяки такій структурі проєкт **масштабується** без суттєвих змін коду компонентів і забезпечує швидку адаптацію до нових вимог.
