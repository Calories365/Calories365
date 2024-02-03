<script>
export default {
    name: "Instructions"
}
</script>
<template>
    <section class="page-cabinet_section">
        <div class="page-cabinet_container">
            <div class="page-cabinet_header">

                <h1 class="page-cabinet_title">
                    Личный кабинет
                </h1>

            </div>
            <div class="page-cabinet_content">
                <div class="page-cabinet_top top-info">
                    <div class="top-info_avatar">
                        <img src="@/assets/valera.jpeg">
                    </div>
                </div>
                <div class="page-cabinet_mid mid-info">
                    <div class="mid-info_login">
                        <div class="mid-info_text">Логин:</div>
                        <div class="mid-info_credentials"></div>
                    </div>
                    <div class="mid-info_email">
                        <div class="mid-info_text">Почта:</div>
                        <div class="mid-info_credentials"></div>
                    </div>
                    <div class="mid-info_email">
                        <div class="mid-info_text">Старый пароль:</div>
                        <div class="mid-info_credentials"></div>
                    </div>
                    <div class="mid-info_email">
                        <div class="mid-info_text">Новый пароль:</div>
                        <div class="mid-info_credentials"></div>
                    </div>

                </div>
            </div>
            <div class="page-cabinet_btn">
                <!-- Пользователь авторизован, показываем кнопку выхода -->
                <a href="#" class="main-header_signout">
                    <div>Выйти</div>
                </a>

            </div>
        </div>
    </section>

</template>

<style scoped lang="scss">
.page-cabinet {
    width: 100%;

    &_section {
        background-color: white;
        height: 100%;
        width: 100%;
        border: 1px solid black;
    }

    &_container {
        max-width: 1400px;
        padding: 0 10px;
        margin: 0 auto;
        height: 100%;
        width: 100%;

        @media (max-width: $bp-large) {
            max-width: 1200px;
        }
    }

    &_header {
    }


    &_title {
        font-size: 30px;
        text-align: center;
        letter-spacing: 0.0625rem;
        //text-transform: uppercase;
        font-weight: 700;
        color: black;
        padding: 30px 0;
    }

    &_content {
        display: flex;
        //justify-content: space-between;
        width: 100%;
        background: #F2F2F2;
        border-radius: 5px;

    }

    &_top {
    }

    &_mid {
    }

    &_bottom {

    }

    &_btn {
        display: flex;
        justify-content: flex-end;
        margin: 50px 0;

        .main-header_signout {
            background-color: red;
            padding: 10px 25px;

            &:hover {
                background-color: #cc1515;
            }
        }

        .main-header_save {
            background-color: $main-color;
            padding: 10px 25px;

            &:hover {
                background-color: #cc1515;
            }
        }
    }
}

.top-info {
    display: flex;
    align-items: center;
    gap: 100px;
    padding: 40px 25px;
    border-bottom: 1px solid #dedede;

    &_avatar {
        width: 150px; /* Ширина */
        height: 150px; /* Высота */
        border-radius: 50%; /* Делаем круг */
        overflow: hidden; /* Скрываем лишнее */
        display: flex; /* Используем Flexbox для центрирования изображения */
        justify-content: center; /* Центрирование по горизонтали */
        align-items: center; /* Центрирование по вертикали */
        img {
            width: 100%; /* Задаем ширину изображения 100% от родителя */
            height: 100%; /* Высота изменяется автоматически */
            /* Если изображение не квадратное, вы можете добавить object-fit для корректного заполнения пространства */
            object-fit: cover;
        }
    }

    &_name {
        flex: 1;
        color: black;
        font-size: 30px;
        letter-spacing: 0.0625rem;

        span {
            font-weight: 500;

        }
    }
}

.mid-info {
    width: 80%;
    padding: 40px 25px;
    font-size: 18px;
    line-height: 32px;
    letter-spacing: 0.0625rem;
    display: flex;
    flex-direction: column;
    gap: 20px;

    &_login, &_email {
        background-color: white;
        width: 80%;
        display: flex;
        gap: 25px;
        border-radius: 5px;
        //margin-left: 100px;
        padding: 10px 10px 10px 10px;
        flex-wrap: wrap;
        overflow-y: hidden;
        overflow-x: scroll;
    }

    &_text {
        color: #eeb82c;
        font-weight: 700;
    }

    &_credentials {
        flex: 1;

    }
}
</style>
