<template>
  <v-app>
    <!-- <v-card class="mx-auto overflow-hidden" height="400" width="344">
      <v-system-bar color="deep-purple darken-3"></v-system-bar>

      <v-app-bar color="deep-purple accent-4" dark prominent>
        <v-toolbar-title>My files</v-toolbar-title>

        <v-spacer></v-spacer>
        <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
        <v-btn icon>
          <v-icon>mdi-magnify</v-icon>
        </v-btn>

        <v-btn icon>
          <v-icon>mdi-filter</v-icon>
        </v-btn>

        <v-btn icon>
          <v-icon>mdi-dots-vertical</v-icon>
        </v-btn>
      </v-app-bar>

      <v-navigation-drawer v-model="drawer" absolute bottom temporary>
        <v-list nav dense>
          <v-list-item-group
            v-model="group"
            active-class="deep-purple--text text--accent-4"
          >
            <v-list-item>
              <v-list-item-title>Foo</v-list-item-title>
            </v-list-item>

            <v-list-item>
              <v-list-item-title>Bar</v-list-item-title>
            </v-list-item>

            <v-list-item>
              <v-list-item-title>Fizz</v-list-item-title>
            </v-list-item>

            <v-list-item>
              <v-list-item-title>Buzz</v-list-item-title>
            </v-list-item>
          </v-list-item-group>
        </v-list>
      </v-navigation-drawer>

      <v-card-text>
        The navigation drawer will appear from the bottom on smaller size
        screens.
      </v-card-text>
    </v-card> -->
    <!--<v-navigation-drawer
      expand-on-hover
      rail
      v-model="drawer"
      dark
      :clipped="clipped"
      fixed
      app
      :color="sideBarcolor"
      :width="150"
      v-if="displayLeftMenu"
    >
      <br />
      <v-list
        v-for="(i, idx) in items"
        :key="idx"
        style="padding: 5px 0 0 0px"
        :title="i.title"
      >
        <v-list-item
          :to="i.module != 'dashboard' ? i.to : ''"
          @click="getTopMenuItems(i)"
          router
          v-if="!i.hasChildren"
          :class="!miniVariant || 'pl-2'"
          vertical
          style="display: inline-block"
          :title="i.title"
        >
          <v-list-item-icon class="ma-2" :title="i.title">
            <v-icon>{{ i.icon }}</v-icon>
          </v-list-item-icon>

          <v-list-item-title class="text-center p-2">
            {{ i.title }}&nbsp;
          </v-list-item-title>
        </v-list-item>
        <v-list-item
          v-else
          :class="!miniVariant || 'pl-2'"
          @click="i.open_menu = !i.open_menu"
        >
          <v-list-item-icon class="mx-2">
            <v-icon>{{ i.icon }}</v-icon>
            <v-icon v-if="miniVariant" small
              >{{ !i.open_menu ? "mdi-chevron-down" : "mdi-chevron-up" }}
            </v-icon>
          </v-list-item-icon>

          <v-list-item-title>{{ i.title }} </v-list-item-title>
          <v-icon small
            >{{ !i.open_menu ? "mdi-chevron-down" : "mdi-chevron-up" }}
          </v-icon>
        </v-list-item>
        <div v-if="i.open_menu">
          <div v-for="(j, jdx) in i.hasChildren" :key="jdx">
            <v-tooltip
              style="margin-left: 25px"
              v-if="miniVariant"
              right
              color="primary"
            >
              <template v-slot:activator="{ on, attrs }">
                <v-list-item
                  v-bind="attrs"
                  v-on="on"
                  style="min-height: 0"
                  :to="j.to"
                  class="submenutitle"
                >
                  <v-list-item-title class="my-2">
                    {{ j.title }}
                  </v-list-item-title>

                  <v-list-item-icon
                    :style="miniVariant ? 'margin-left: -54px;' : ''"
                  >
                    <v-icon
                      :to="j.to"
                      :style="miniVariant ? 'margin-left: 12px;' : ''"
                    >
                      {{ j.icon }}
                    </v-icon>
                  </v-list-item-icon>
                </v-list-item>
              </template>
<span>{{ j.title }}</span>
</v-tooltip>

<v-list-item v-else style="min-height: 0; margin-left: 50px" :to="j.to" class="submenutitle">
  <v-list-item-title v-if="can(j.menu)" class="my-2">
    {{ j.title }}
  </v-list-item-title>
</v-list-item>
</div>
</div>
</v-list>
<v-list style="padding: 5px 0 0 0px">
  <v-list-item @click="goToPage('/alarm/dashboard')" router :class="!miniVariant || 'pl-2'" vertical
    style="display: inline-block">
    <v-list-item-icon class="ma-2">
      <v-icon>mdi mdi-view-dashboard</v-icon>
    </v-list-item-icon>

    <v-list-item-title class="text-center p-2">
      Dashboard
    </v-list-item-title>
  </v-list-item>
  <v-list-item @click="goToPage('/alarm/temperatureLogs')" router :class="!miniVariant || 'pl-2'" vertical
    style="display: inline-block">
    <v-list-item-icon class="ma-2">
      <v-icon>mdi mdi-fire</v-icon>
    </v-list-item-icon>

    <v-list-item-title class="text-center p-2">
      Temperature Reports
    </v-list-item-title>
  </v-list-item>
  <v-list-item @click="goToPage('/alarm/AlarmLogs')" router :class="!miniVariant || 'pl-2'" vertical
    style="display: inline-block">
    <v-list-item-icon class="ma-2">
      <v-icon>mdi-alarm-light</v-icon>
    </v-list-item-icon>

    <v-list-item-title class="text-center p-2">
      Alarm Reports</v-list-item-title>
  </v-list-item>
  <v-list-item @click="goToPage('/alarm/rooms')" router :class="!miniVariant || 'pl-2'" vertical
    style="display: inline-block">
    <v-list-item-icon class="ma-2">
      <v-icon>mdi mdi-server</v-icon>
    </v-list-item-icon>

    <v-list-item-title class="text-center p-2">
      Server Rooms
    </v-list-item-title>
  </v-list-item>
</v-list>
</v-navigation-drawer>-->
    <!-- <v-navigation-drawer v-model="drawer" absolute bottom temporary>
      <v-list nav dense>
        <v-list-item-group
          v-model="group"
          active-class="deep-purple--text text--accent-4"
        >
          <v-list-item>
            <v-list-item-title>Foo</v-list-item-title>
          </v-list-item>

          <v-list-item>
            <v-list-item-title>Bar</v-list-item-title>
          </v-list-item>

          <v-list-item>
            <v-list-item-title>Fizz</v-list-item-title>
          </v-list-item>

          <v-list-item>
            <v-list-item-title>Buzz</v-list-item-title>
          </v-list-item>
        </v-list-item-group>
      </v-list>
    </v-navigation-drawer> -->

    <v-navigation-drawer v-if="$vuetify.breakpoint.smAndDown" :temporary="$vuetify.breakpoint.smAndDown" rail
      v-model="drawer" dark :clipped="clipped" fixed app :color="sideBarcolor" :width="115">
      <br />

      <v-list style="padding: 5px 0 0 0px">
        <v-list-item @click="drawer = !drawer" router :class="!miniVariant || 'pl-2'" vertical
          style="display: inline-block">
          <v-list-item-icon class="ma-2">
            <v-icon color="red">mdi mdi-close-circle</v-icon>
          </v-list-item-icon>

          <v-list-item-title class="text-center p-2"> Close </v-list-item-title>
        </v-list-item>
        <v-list-item :key="index" v-for="(items, index) in company_top_menu" router
          :class="'pa-2 ' + (!miniVariant || 'pl-2') + ' '" vertical style="display: inline-block"
          @click="setSubLeftMenuItems(items.menu, items.to)" :color="menuProperties[items.menu] && menuProperties[items.menu].selected
            " :style="$vuetify?.theme.dark && menuProperties[items.menu]?.selected
              ? 'background-color: #007d61; color: white;'
              : ''
              ">
          <v-list-item-icon class="ma-2">
            <v-icon v-if="items.menu == 'dashboard'">mdi mdi-view-dashboard</v-icon>
            <!-- <v-icon v-else-if="items.menu == 'dashboard'">mdi mdi-fire</v-icon> -->

            <v-icon v-else-if="items.menu == 'logs'">mdi-thermometer </v-icon>
            <v-icon v-else-if="items.menu == 'reports'">mdi-alarm-light</v-icon>

            <v-icon v-else-if="items.menu == 'rooms'">mdi mdi-server</v-icon>

            <v-icon v-else-if="items.menu == 'whatsapp'">mdi mdi-whatsapp</v-icon>
            <v-icon v-else-if="items.menu == 'notifications'">mdi-email</v-icon>

            <v-icon v-else>mdi mdi-menu</v-icon>
          </v-list-item-icon>

          <v-list-item-title class="text-center p-2">
            {{ items.title }}
          </v-list-item-title>
        </v-list-item>

        <!-- <v-list-item
          @click="goToPage('/alarm/dashboard')"
          router
          :class="!miniVariant || 'pl-2'"
          vertical
          style="display: inline-block"
          :color="
            menuProperties['dashboard'] && menuProperties['dashboard'].selected
          "
          :style="
            $vuetify.theme.dark && menuProperties['dashboard']?.selected
              ? 'background-color: #007d61; color: white;'
              : ''
          "
        >
          <v-list-item-icon class="ma-2">
            <v-icon>mdi mdi-view-dashboard</v-icon>
          </v-list-item-icon>

          <v-list-item-title class="text-center p-2">
            Dashboard
          </v-list-item-title>
        </v-list-item>
        <v-list-item
          @click="goToPage('/alarm/temperatureLogs')"
          router
          :class="!miniVariant || 'pl-2'"
          vertical
          style="display: inline-block"
          :color="menuProperties['logs'] && menuProperties['logs'].selected"
          :style="
            $vuetify.theme.dark && menuProperties['logs']?.selected
              ? 'background-color: #007d61; color: white;'
              : ''
          "
        >
          <v-list-item-icon class="ma-2">
            <v-icon>mdi mdi-fire</v-icon>
          </v-list-item-icon>

          <v-list-item-title class="text-center p-2">
            Temperature Reports
          </v-list-item-title>
        </v-list-item>
        <v-list-item
          @click="goToPage('/alarm/AlarmLogs')"
          router
          :class="!miniVariant || 'pl-2'"
          vertical
          style="display: inline-block"
          :color="
            menuProperties['reports'] && menuProperties['reports'].selected
          "
          :style="
            $vuetify.theme.dark && menuProperties['reports']?.selected
              ? 'background-color: #007d61; color: white;'
              : ''
          "
        >
          <v-list-item-icon class="ma-2">
            <v-icon>mdi-alarm-light</v-icon>
          </v-list-item-icon>

          <v-list-item-title class="text-center p-2">
            Alarm Reports</v-list-item-title
          >
        </v-list-item>
        <v-list-item
          @click="goToPage('/alarm/rooms')"
          router
          :class="!miniVariant || 'pl-2'"
          vertical
          style="display: inline-block"
          :color="menuProperties['rooms'] && menuProperties['rooms'].selected"
          :style="
            $vuetify.theme.dark && menuProperties['rooms']?.selected
              ? 'background-color: #007d61; color: white;'
              : ''
          "
        >
          <v-list-item-icon class="ma-2">
            <v-icon>mdi mdi-server</v-icon>
          </v-list-item-icon>

          <v-list-item-title class="text-center p-2">
            Server Rooms
          </v-list-item-title>
        </v-list-item> -->
      </v-list>
    </v-navigation-drawer>
    <v-app-bar :color="appBarColor" :clipped-left="clipped" fixed app class="app-header-main"
      :style="$nuxt.$route.name == 'index' ? 'z-index: 100000' : ''">
      <v-app-bar-nav-icon v-if="$vuetify.breakpoint.smAndDown" @click.stop="drawer = !drawer" />
      <span class="text-overflow" style="cursor: pointer" @click="gotoHomePage()">
        <img title="Xtream Guard" :src="logo_src" :style="$vuetify.breakpoint.smAndDown
          ? 'margin-top:10px;margin-left:-10px;width:125px'
          : 'width: 150px'
          " />
      </span>

      <v-spacer></v-spacer>

      <span :style="$vuetify.breakpoint.smAndDown ? 'display:none' : 'display:block'
        ">
        <!-- TOP MENU -->
        <template v-if="
          getLoginType == 'company' ||
          getLoginType == 'branch' ||
          (getLoginType == 'employee' &&
            $auth.user.role?.role_type.toLowerCase() != 'guard' &&
            $auth.user.role?.role_type.toLowerCase() != 'host')
        ">
          <v-row align="center" justify="space-around" class="">
            <v-col :key="index" v-for="(items, index) in company_top_menu">
              <v-btn small text class="btn-text-size" :elevation="0" color="dark" fill
                @click="setSubLeftMenuItems(items.menu, items.to)" :color="menuProperties[items.menu] &&
                  menuProperties[items.menu].selected
                  " :style="$vuetify?.theme.dark && menuProperties[items.menu]?.selected
                    ? 'background-color: #007d61; color: white;'
                    : ''
                    ">
                <span style="color: #fff !important">{{ items.title }}</span>
              </v-btn>
            </v-col>
          </v-row>
        </template>
      </span>
      <v-spacer></v-spacer>

      <!-- <span class="header-menu">
        <template
          v-if="
            getLoginType == 'company' ||
            getLoginType == 'branch' ||
            getLoginType == 'department' ||
            ($auth.user?.role?.role_type?.toLowerCase() != 'guard' &&
              $auth.user?.role?.role_type?.toLowerCase() != 'host')
          "
        >
          <v-row align="center" justify="space-around" class="header-menu-row">
            <v-col
              v-for="(items, index) in items"
              :key="index"
              class="header-menu-box"
            >
              <v-btn
                class="header-menu-button"
                small
                text
                :elevation="0"
                :color="
                  menuProperties[items.menu] &&
                  menuProperties[items.menu].selected
                "
                fill
                @click="setSubLeftMenuItems(items.menu, items.to)"
              >
                <b class="header-menu-item"> {{ items.title }} </b>
              </v-btn>
            </v-col>
          </v-row>
        </template>
      </span> -->

      <span>
        <v-form autocomplete="off"> </v-form>
      </span>
      <v-spacer></v-spacer>
      <span v-if="!$vuetify.breakpoint.smAndDown" style="
          font-size: 30px;
          color: #fff;
          padding-right: 75px;
          min-width: 300px;
        ">
        <div class="livedateTime" :style="$vuetify.breakpoint.smAndDown ? 'float:right' : ' '">
          <v-icon size="35" style="">mdi-clock-outline</v-icon>
          <span :style="$vuetify.breakpoint.smAndDown
            ? 'font-size: 21px;'
            : 'font-size: 30px;'
            ">
            {{ currentTime }}</span>
          <span :style="$vuetify.breakpoint.smAndDown
            ? 'font-size: 10px;display:block'
            : 'font-size: 16px;' + 'font-weight: 200;'
            ">{{ todayDate }}</span>
        </div>
      </span>
      <v-menu nudge-bottom="50" transition="scale-transition" origin="center center" bottom left min-width="200"
        nudge-left="20" style="z-index: 9999">
        <template v-slot:activator="{ on, attrs }">
          <v-btn icon color="red" v-bind="attrs" v-on="on">
            <v-avatar size="35" style="border: 1px solid #fff">
              <v-img class="company_logo" :src="getLogo"></v-img>
            </v-avatar>
          </v-btn>
        </template>

        <v-list light nav dense>
          <v-list-item-group color="white">
            <v-list-item v-if="this.$auth && this.$auth.user.user_type == 'company'" @click="goToCompany()">
              <v-list-item-icon>
                <v-icon>mdi-account-multiple-outline</v-icon>
              </v-list-item-icon>
              <v-list-item-content>
                <v-list-item-title class="black--text">Profile</v-list-item-title>
              </v-list-item-content>
            </v-list-item>

            <v-list-item @click="logout">
              <v-list-item-icon>
                <v-icon>mdi-logout</v-icon>
              </v-list-item-icon>
              <v-list-item-content>
                <v-list-item-title class="black--text">Logout</v-list-item-title>
              </v-list-item-content>
            </v-list-item>
          </v-list-item-group>
        </v-list>
      </v-menu>
      <!-- <button type="button" @click="playAudioOnUserInteraction" ref="myBtn">
        Click Me!
      </button> -->
      <!-- <v-btn
        v-if="getLoginType == 'company' || getLoginType == 'branch'"
        icon
        plan
        @click="gotoHomePage2()"
        ><v-icon color="black" style="text-align: center"
          >mdi-view-dashboard</v-icon
        ></v-btn
      >
      <v-btn
        v-if="getLoginType == 'company' || getLoginType == 'branch'"
        icon
        plan
        @click="gotoHomePage()"
        class="mr-3"
        ><v-icon style="text-align: center">mdi-view-dashboard</v-icon></v-btn
      > -->
      <!-- <v-btn
        v-if="
          (getLoginType == 'company' || getLoginType == 'branch') &&
          !this.$vuetify.theme.dark
        "
        icon
        plan
        @click="toggleTheme()"
        class="mr-3"
      >
        <v-icon class="white--black" style="text-align: center"
          >mdi-swap-horizontal-circle
        </v-icon></v-btn
      >
      <v-btn @click="switchTheme('light')">Light Theme</v-btn>
      <v-btn @click="switchTheme('dark')">Dark Theme</v-btn>
      <v-btn @click="switchTheme('blue')">Blue Theme</v-btn>

      <v-alert type="info" class="mt-4">
        {{ currentTheme }}
      </v-alert> -->
      <v-btn v-if="getLoginType == 'company' || getLoginType == 'branch'" icon plan @click="toggleTheme()" class="mr-3">
        <v-icon style="text-align: center; color: #fff">
          mdi-palette
        </v-icon></v-btn>

      <!-- <v-btn icon dark @click="openNotificationPopup()">
        <v-badge :color="'  ' + pendingNotificationsCount > 0 ? 'red' : 'green'" :content="pendingNotificationsCount == '' ? '0' : pendingNotificationsCount
          " style="top: 10px; left: -19px; z-index: 9999 !important">
          <v-icon style="top: -10px; left: 10px" class="violet--text">mdi mdi-bell-ring</v-icon>
        </v-badge>
      </v-btn> -->

      <v-btn v-if="getLoginType == 'company' || getLoginType == 'branch'" icon plan @click="goToSettings()"
        class="mr-3"><v-icon class="white--text" style="text-align: center">mdi-settings</v-icon></v-btn>

      <!-- <v-menu
        bottom
        origin="center center"
        offset-y
        transition="scale-transition"
      >
        <template v-slot:activator="{ on, attrs }">
          <v-btn icon dark v-bind="attrs" v-on="on">
            <v-badge
              :color="pendingNotificationsCount > 0 ? 'red' : 'green'"
              :content="
                pendingNotificationsCount == ''
                  ? '0'
                  : pendingNotificationsCount
              "
              style="top: 10px; left: -19px"
            >
              <v-icon style="top: -10px; left: 10px" class="violet--text"
                >mdi mdi-bell-ring</v-icon
              >
            </v-badge>
          </v-btn>
        </template>
        <v-list>
          <v-list-item
            style="height: 30px"
            @click="goToPage(item.click)"
            v-for="(item, index) in notificationsMenuItems"
            :key="index"
          >
            <v-list-item-icon>
              <v-icon>{{ item.icon }}</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
              <v-list-item-title class="black--text">{{
                item.title
              }}</v-list-item-title>
            </v-list-item-content>

          </v-list-item>
        </v-list>
      </v-menu> -->

      <!-- <v-menu
        bottom
        origin="center center"
        offset-y
        transition="scale-transition"
      >
        <template v-slot:activator="{ on, attrs }">
          <v-badge
            v-bind="attrs"
            v-on="on"
            :color="pendingLeavesCount > 0 ? 'red' : 'red'"
            content="1"
            style="top: 10px; left: -19px"
          >
            <v-icon style="top: -10px; left: 10px" class="violet--text"
              >mdi mdi-bell-ring</v-icon
            >
          </v-badge>
        </template>

        <v-list>
          <v-list-item v-for="(item, i) in notificationItems" :key="i">
            <v-list-item-title>{{ item.title }}</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu> -->
      <!-- <label class=" ">
        <v-badge
          v-if="pendingLeavesCount > 0"
          @click="navigateToLeavePage()"
          :color="pendingLeavesCount > 0 ? 'red' : 'white'"
          :content="pendingLeavesCount"
        >
          <v-icon class="violet--text" @click="navigateToLeavePage()"
            >mdi mdi-bell-ring</v-icon
          >
        </v-badge>
        <v-badge v-else @click="navigateToLeavePage()" content="0">
          <v-icon
            style="color: #e91919 !important"
            @click="navigateToLeavePage()"
            >mdi mdi-bell-ring</v-icon
          >
        </v-badge>
      </label> -->
      <v-snackbar top="top" v-model="snackNotification" location="right" :color="snackNotificationColor" timeout="2000">
        {{ snackNotificationText }}

        <template v-slot:action="{ attrs }">
          <v-btn v-bind="attrs" text @click="snackNotification = false">
            Close
          </v-btn>
        </template>
      </v-snackbar>

      <v-dialog v-model="alarmNotificationStatus" transition="dialog-top-transition" max-width="800"
        style="z-index: 9999">
        <!-- <template v-slot:activator="{ on, attrs }">
          <v-btn color="primary" v-bind="attrs" v-on="on">From the top</v-btn>
        </template> -->
        <template v-slot:default="dialog">
          <v-card>
            <!-- <v-card-title dense class="popup_background">
              <span>Alarm</span>
              <v-spacer></v-spacer>
              <v-icon @click="alarmNotificationStatus = false" outlined>
                mdi mdi-close-circle
              </v-icon>
            </v-card-title> -->

            <!-- <div @click="s()" class="toggle-sound"></div> -->
            <v-toolbar color="error" dense flat style="background-color: red !important">
              <v-toolbar-title class="white--text text-center" style="width: 100%">
                Attention : Alarm Notification
              </v-toolbar-title>

              <v-spacer></v-spacer>

              <v-icon class="white--text" @click="
                alarmNotificationStatus = false;
              stopsound();
              hidepopupfor5Minutes();
              ">
                mdi-close-circle
              </v-icon>
            </v-toolbar>

            <v-card-text class="pt-5">
              <v-row :key="index" v-for="(device, index) in notificationAlarmDevices">
                <v-col cols="12">
                  <v-row v-if="device.temperature_alarm" :style="pendingNotificationsCount > 1
                    ? 'border-bottom: 1px solid #474141'
                    : ''
                    ">
                    <v-col cols="12" md="3" class="pt-10 text-center"><img src="../static/alarm-icons/temperature.png"
                        :width="$vuetify.breakpoint.smAndDown ? '60px' : '70px'
                          " /></v-col>
                    <v-col cols="12" md="9" class="pl-4">
                      <div class="pa-3 pt-0" style="font-size: 16px; font-weight: bold">
                        Temperature Threshold Alarm Triggered at :
                        {{ $dateFormat.format5(device.alarm_start_datetime) }}
                      </div>
                      <div class="bold1 pa-1" v-if="device.device_temperature_sensor">
                        Sensor Room :
                        {{ device.device_temperature_sensor.name }}
                      </div>
                      <div class="bold1 pa-1">
                        Device Name : {{ device.device.name }}
                      </div>
                      <div class="bold1 pa-1">
                        Temperature : {{ device.temparature }}
                        <span style="font-size: 12px">°C</span>
                      </div>
                      <div class="bold1 pa-1">
                        Threshold Max : {{ device.temperature_max }}
                        <span style="font-size: 12px">°C</span>
                      </div>
                      <!-- <div class="bold1 pa-1">
                        Branch Name :{{  device.device.branch?.branch_name }}
                      </div> -->
                      <!-- <div class="bold1 pa-1">
                        Device Location :{{ device.device.location }}
                      </div> -->
                    </v-col>
                  </v-row>
                  <v-row v-if="device.fire_alarm" :style="pendingNotificationsCount > 1
                    ? 'border-bottom: 1px solid #474141'
                    : ''
                    ">
                    <v-col cols="2" class="pt-10 text-center"><img src="../static/alarm-icons/smoke_alarm.png" :width="$vuetify.breakpoint.smAndDown ? '60px' : '70px'
                      " /></v-col>
                    <v-col cols="10" class="pl-4">
                      <div class="pa-3 pt-0" style="font-size: 18px; font-weight: bold">
                        Fire Alarm Triggered at :
                        {{ $dateFormat.format5(device.alarm_start_datetime) }}
                      </div>
                      <div class="bold1 pa-1">
                        Device Name :{{ device.device.name }}
                      </div>
                      <!-- <div class="bold1 pa-1">
                        Branch Name :{{ device.device.branch?.branch_name }}
                      </div> -->
                      <!-- <div class="bold1 pa-1">
                        Device Location :{{ device.device.location }}
                      </div> -->
                    </v-col>
                  </v-row>

                  <v-row v-if="device.smoke_alarm" :style="pendingNotificationsCount > 1
                    ? 'border-bottom: 1px solid #474141'
                    : ''
                    ">
                    <v-col cols="2" class="pt-10 text-center"><img src="../static/alarm-icons/smoke_alarm.png" :width="$vuetify.breakpoint.smAndDown ? '60px' : '70px'
                      " /></v-col>
                    <v-col cols="10" class="pl-4">
                      <div class="pa-3 pt-0" style="font-size: 18px; font-weight: bold">
                        Smoke Alarm Triggered at :
                        {{ $dateFormat.format5(device.alarm_start_datetime) }}
                      </div>
                      <div class="bold1 pa-1">
                        Device Name :{{ device.device.name }}
                      </div>
                      <!-- <div class="bold1 pa-1">
                        Branch Name :{{ device.device.branch?.branch_name }}
                      </div> -->
                      <!-- <div class="bold1 pa-1">
                        Device Location :{{ device.device.location }}
                      </div> -->
                    </v-col>
                  </v-row>

                  <v-row v-if="device.door_status" :style="pendingNotificationsCount > 1
                    ? 'border-bottom: 1px solid #474141'
                    : ''
                    ">
                    <v-col cols="2" class="pt-10 text-center"><img src="../static/alarm-icons/dooropen.png" :width="$vuetify.breakpoint.smAndDown ? '60px' : '70px'
                      " /></v-col>
                    <v-col cols="10" class="pl-4">
                      <div class="pa-3 pt-0" style="font-size: 18px; font-weight: bold">
                        Door Keep Open Alarm Triggered at :
                        {{ $dateFormat.format5(device.alarm_start_datetime) }}
                      </div>
                      <div class="bold1 pa-1">
                        Device Name :{{ device.device.name }}
                      </div>
                      <div class="bold1 pa-1">
                        Branch Name :{{ device.device.branch?.branch_name }}
                      </div>
                      <!-- <div class="bold1 pa-1">
                        Device Location :{{ device.device.location }}
                      </div> -->
                    </v-col>
                  </v-row>

                  <v-row v-if="device.power_failure" :style="pendingNotificationsCount > 1
                    ? 'border-bottom: 1px solid #474141'
                    : ''
                    ">
                    <v-col cols="2" class="pt-10 text-center"><img src="../static/alarm-icons/acpower.png" :width="$vuetify.breakpoint.smAndDown ? '60px' : '70px'
                      " /></v-col>
                    <v-col cols="10" class="pl-4">
                      <div class="pa-3 pt-0" style="font-size: 18px; font-weight: bold">
                        AC Power Off Alarm Triggered at :
                        {{ $dateFormat.format5(device.alarm_start_datetime) }}
                      </div>
                      <div class="bold1 pa-1">
                        Device Name :{{ device.device.name }}
                      </div>
                      <!-- <div class="bold1 pa-1">
                        Branch Name :{{ device.device.branch?.branch_name }}
                      </div> -->
                      <!-- <div class="bold1 pa-1">
                        Device Location :{{ device.device.location }}
                      </div> -->
                    </v-col>
                  </v-row>

                  <v-row v-if="device.water_leakage" :style="pendingNotificationsCount > 1
                    ? 'border-bottom: 1px solid #474141'
                    : ''
                    ">
                    <v-col cols="2" class="pt-10 text-center">
                      <img src="../static/alarm-icons/water-leakage.png" :width="$vuetify.breakpoint.smAndDown ? '60px' : '70px'
                        " /></v-col>
                    <v-col cols="10" class="pl-4">
                      <div class="pa-3 pt-0" style="font-size: 18px; font-weight: bold">
                        Water Leakage Alarm Triggered at :
                        {{ $dateFormat.format5(device.alarm_start_datetime) }}
                      </div>
                      <div class="bold1 pa-1">
                        Device Name :{{ device.device.name }}
                      </div>
                      <!-- <div class="bold1 pa-1">
                        Branch Name :{{ device.device.branch?.branch_name }}
                      </div> -->
                      <!-- <div class="bold1 pa-1">
                        Device Location :{{ device.device.location }}
                      </div> -->
                    </v-col>
                  </v-row>
                </v-col>
              </v-row>

              <div>
                <!-- <div style="color: green">
                  <strong>Note: </strong>All Branch Level Doors are Opened
                </div>
                <br />
                Check Devices list and Turn off Alarm to Close this popup. -->
                <!-- Check Devices list
                <v-spacer></v-spacer>
                <v-btn
                  style="float: right"
                  color="error"
                  @click="
                    goToPage('/device');
                    alarmNotificationStatus = false;
                  "
                  >View Devices</v-btn
                > -->
              </div>
            </v-card-text>
            <!-- <v-card-actions class="justify-end">

              <v-btn
                style="float: right"
                color="error"
                @click="
                  goToPage('/device');
                  alarmNotificationStatus = false;
                "
                >View Devices</v-btn
              >
            </v-card-actions> -->
          </v-card>
        </template>
      </v-dialog>
    </v-app-bar>

    <!-- <v-main
      :class="'main_bg main_bg_header main_bg_' + pagename"
      :style="
        miniVariant && drawer
          ? 'padding-left: 60px;'
          : !drawer
          ? 'padding-left: 5px;'
          : 'padding-left: 140px;'
      "
    > -->
    <v-main :class="'main_bg main_bg_header main_bg_' + pagename">
      <v-container style="max-width: 100%">
        <nuxt />
      </v-container>
    </v-main>

    <!-- <v-navigation-drawer
      v-model="rightDrawer"
      :clipped="true"
      :right="right"
      fixed
      style="z-index: 1000"
    >
      <v-row style="margin-top: 50px">
        <v-col>
          <v-card class="pa-2" elevation="0">
            <v-col cols="12">
              <div class="mb-3">
                <Strong>Theme</Strong>
              </div>
              <div
                class="btn-group"
                role="group"
                aria-label="Basic radio toggle button group"
              >
                <input
                  type="radio"
                  class="btn-check"
                  name="theme"
                  id="light"
                  autocomplete="off"
                  @click="changeTheme('light')"
                />
                <label class="btn" :class="'btn-outline-dark'" for="light"
                  >Light</label
                >
                <input
                  type="radio"
                  class="btn-check"
                  name="theme"
                  id="dark"
                  autocomplete="off"
                  @click="changeTheme('dark')"
                />
                <label class="btn btn-outline-dark" for="dark">Dark</label>
              </div>
            </v-col>
            <v-divider></v-divider>
            <v-col cols="12">
              <div class="mb-3">
                <Strong>Top Bar</Strong>
              </div>
              <div class="d-flex">
                <v-btn
                  class="mx-2 stg-color-icon"
                  fab
                  dark
                  x-small
                  color="primary"
                  @click="changeTopBarColor('primary')"
                ></v-btn>
                <v-btn
                  class="mx-2 stg-color-icon"
                  fab
                  dark
                  x-small
                  color="error"
                  @click="changeTopBarColor('error')"
                ></v-btn>
                <v-btn
                  class="mx-2 stg-color-icon"
                  fab
                  dark
                  x-small
                  color="indigo"
                  @click="changeTopBarColor('indigo')"
                ></v-btn>
                <v-btn
                  class="mx-2 stg-color-icon"
                  fab
                  dark
                  x-small
                  color="background"
                  @click="changeTopBarColor('background')"
                ></v-btn>
              </div>
            </v-col>
            <v-divider></v-divider>
            <v-col cols="12">
              <div class="mb-3">
                <Strong>Side Bar</Strong>
              </div>
              <div class="d-flex">
                <v-btn
                  class="mx-2 stg-color-icon"
                  fab
                  dark
                  x-small
                  color="primary"
                  @click="changeSideBarColor('primary')"
                ></v-btn>
                <v-btn
                  class="mx-2 stg-color-icon"
                  fab
                  dark
                  x-small
                  color="error"
                  @click="changeSideBarColor('error')"
                ></v-btn>
                <v-btn
                  class="mx-2 stg-color-icon"
                  fab
                  dark
                  x-small
                  color="indigo"
                  @click="changeSideBarColor('indigo')"
                ></v-btn>
                <v-btn
                  class="mx-2 stg-color-icon"
                  fab
                  dark
                  x-small
                  color="background"
                  @click="changeSideBarColor('background')"
                >
                </v-btn>
              </div>
            </v-col>
          </v-card>
        </v-col>
      </v-row>
    </v-navigation-drawer> -->
  </v-app>
</template>

<script>
// import colors from "vuetify/lib/util/colors";
// import vuetify from "@/plugins/vuetify";

import company_menus from "../menus/company.json";
import employee_menus from "../menus/employee.json";
import branch_menus from "../menus/branch.json";
import guard_menus from "../menus/guard.json";
import host_menus from "../menus/host.json";

import company_top_menu from "../menus/company_modules_top.json";
import employee_top_menu from "../menus/employee_modules_top.json";

export default {
  data() {
    return {
      group: null,

      popupNotificationHide5MinutesActive: false,
      displayLeftMenu: false,
      currentTime: "00:00:00",
      todayDate: "---",
      notificationsMenuItems: [
        {
          title: "Leaves Pending (0)",
          click: "/leaves",
          icon: "mdi-calendar-account",
          key: "leaves",
        },
        {
          title: "Visitors Pending (0)",
          click: "/visitor/requests",
          icon: "mdi-transit-transfer",
          key: "visitors",
        },
      ],
      notificationAlarmDevices: {},
      selectedBranchName: "All Branches",
      seelctedBranchId: "",
      branch_id: "",
      menuProperties: {
        dashboard: {
          elevation: 0,
          selected: "",
        },
        logs: {
          elevation: 0,
          selected: "",
        },

        reports: {
          elevation: 0,
          selected: "",
        },
        notifications: {
          elevation: 0,
          selected: "",
        },
        rooms: {
          elevation: 0,
          selected: "",
        }, security: {
          elevation: 0,
          selected: "",
        },
        whatsapp: {
          elevation: 0,
          selected: "",
        }, monitor: {
          elevation: 0,
          selected: "",
        },
      },

      topMenu_Selected: "dashboard",
      company_menus,
      employee_menus,
      branch_menus,
      guard_menus,
      host_menus,
      company_top_menu,
      employee_top_menu,
      pendingLeavesCount: 0,
      pendingNotificationsCount: 0,
      snackNotificationText: "",
      snackNotification: false,
      snackNotificationColor: "black",
      socketConnectionStatus: 0,
      pagename: "",
      right: true,
      rightDrawer: false,
      color: "",
      sideBarcolor: "background",
      year: new Date().getFullYear(),
      dropdown_menus: [{ title: "setting" }, { title: "logout" }],

      clipped: false,
      open_menu: [],
      drawer: false, //true - hide leftmenu
      fixed: false,
      order_count: "",
      // logo_src: "",
      logo_src2: "",
      items: [],
      modules: {
        module_ids: [],
        module_names: [],
      },
      clipped: true,

      miniVariant: false, //hide leftmenu
      title: "Xtreme Guard",
      socket: null,
      logout_btn: {
        icon: "mdi-logout",
        label: "Logout",
      },
      viewing_page_name: "",

      inactivityTimeout: null,
      alarmNotificationStatus: false,
      audio: null,
      timmerStatus: true,
      currentTheme: "light",
      intervalObj: null,
      // themes: {
      //   light: {
      //     primary: "#6946dd",
      //     accent: "#d8363a",
      //     secondary: "#242424",
      //     background: "#34444c",
      //     main_bg: "#ECF0F4",
      //     violet: "#6946dd",
      //     popup_background: "#ecf0f4",
      //   },
      //   dark: {
      //     primary: "#1976D2",
      //     accent: "#82B1FF",
      //     secondary: "#424242",
      //     background: "#1a2632",
      //     main_bg: "#101044",
      //     popup_background: "#E3F2FD",
      //   },
      // },
    };
  },
  // theme: {
  //   dark: true,

  //   themes: {
  //     light: {
  //       //primary: "#5fafa3", //green
  //       primary: "#6946dd", //violoet
  //       accent: "#d8363a",
  //       secondary: "#242424",
  //       background: "#1a2632",
  //       info: colors.teal.lighten1,
  //       warning: colors.amber.base,
  //       error: colors.deepOrange.accent4,
  //       success: colors.green.accent3,
  //       main_bg: "#ECF0F4",
  //       violet: "#6946dd",
  //       popup_background: "#ecf0f4",
  //     },
  //     dark: {
  //       primary: "#000000", // Custom primary color
  //       secondary: "#000000", // Custom secondary color
  //       accent: "#000000", // Custom accent color
  //       background: "#000000", // Background color
  //       surface: "#000000", // Surface color (e.g., cards, sheets)
  //       error: "#000000", // Error color
  //       info: "#000000", // Info color
  //       success: "#000000", // Success color
  //       warning: "#000000", // Warning color

  //       disabled: "#000000", // Disabled element color
  //       divider: "#000000", // Divider color
  //       //custom colors
  //       appBarBackground: "#000000",
  //     },
  //   },
  // },
  async created() {
    await this.loadEnv();
    const savedTheme = this.$auth.user.company?.theme || "light";

    if (savedTheme === "dark") {
      this.$vuetify.theme.dark = true;
    } else {
      this.$vuetify.theme.dark = false;
    }

    this.pagename = this.$route.name;
    if (!this.$auth.user) {
      {
        this.logout();
        return "";
      }
    }
    // this.verifyAlarmStatus();
    //console.log("process.env.BACKEND_URL2", process.env.BACKEND_URL2);
    this.updateTopmenu();

    this.$store.commit("loginType", this.$auth.user.user_type);
    this.getCompanyDetails();
    this.setMenus();
    this.setSubLeftMenuItems("dashboard", "/dashboard", false);
    // this.logo_src = require("@/static/logo.png");

    this.pendingNotificationsCount = 0;

    setInterval(() => {
      const now = new Date();
      // Get the day, month, year, and day of the week
      var day = now.getDate();
      var month = now.getMonth() + 1; // Month is zero-based, so add 1
      var year = now.getFullYear();

      day = (day < 10 ? "0" : "") + day;
      month = (month < 10 ? "0" : "") + month;
      const formattedDateTime = month + "-" + day + "-" + year;

      this.currentTime = now.toLocaleTimeString([], { hour12: false });
      this.todayDate =
        this.$dateFormat.format_date_with_dayname(formattedDateTime);

      try {
        // console.log(localStorage.getItem("alarm"));

        if (localStorage.getItem("alarm") == "true") {
          // if (
          //   this.timmerStatus == true &&
          //   !this.popupNotificationHide5MinutesActive
          // )
          // if (this.timmerStatus == true)
          {
            this.verifyAlarmStatus();
            localStorage.setItem("alarm", false);
          }
        }
      } catch (e) { }
    }, 1000);


  },
  beforeDestroy() {
    if (this.intervalObj) clearInterval(this.intervalObj);
    this.intervalObj = null;
  },
  async mounted() {
    await this.loadEnv();
    setTimeout(() => {
      this.audio = new Audio(
        this.$store.state.env.BACKEND_URL2 + "alarm_sounds/alarm-sound1.mp3"
      );
    }, 2000);
    // setTimeout(() => {
    //   this.palysound();
    // }, 5000);

    // setTimeout(() => {
    //   // this.loadNotificationMenu();
    //   this.verifyAlarmStatus();
    // }, 1000 * 10);

    this.intervalObj = setInterval(() => {
      // console.log(this.popupNotificationHide5MinutesActive);

      this.verifyAlarmStatus();
    }, 1000 * 10);
    // setInterval(() => {
    //   this.loadNotificationMenu();
    // }, 1000 * 60 * 2);
    const now = new Date();
    console.log("reloadCount", now.toLocaleTimeString([], { hour12: false }));
    setTimeout(() => {
      window.location.reload();
    }, 1000 * 60 * 15);
    //this.company_menus = [];

    let menu_name = this.$route.name;
    let bgColor = "dark";
    let loadSelectedMenu = "";

    menu_name = menu_name.replaceAll("-", "/");

    // console.log(this.getLoginType);

    if (this.getLoginType === "company" || this.getLoginType === "branch") {
      //-------------------
      loadSelectedMenu = this.company_menus.filter(
        (item) => item.to === "/" + menu_name && item.submenu == null
      );

      // console.log("menu_name", loadSelectedMenu);

      if (loadSelectedMenu[0]) {
        menu_name = loadSelectedMenu[0].module;

        console.log("menu_name", menu_name);

        if (this.menuProperties.hasOwnProperty(menu_name)) {
          for (const key in this.menuProperties) {
            this.menuProperties[key].elevation = 0;
            this.menuProperties[key].selected = "";
          }

          this.menuProperties[menu_name].elevation = 0;
          this.menuProperties[menu_name].selected = bgColor;
        }
        //Color is changed and Now display sub menu - click - load left sub menu items

        this.items = this.company_menus.filter(
          (item) => item.module === loadSelectedMenu[0].module
        );
      }
    }
    this.setupInactivityDetection();

    // setTimeout(() => {
    //   this.$router.push(`/dashboard2`);
    // }, 1000 * 60 * 15); //15 minutes
  },
  watch: {
    // group() {
    //   this.drawer = false;
    // },
  },
  computed: {
    isDarkTheme() {
      return this.$vuetify?.theme.dark;
    },
    themeClass() {
      return this.isDarkTheme ? "theme-dark-bg" : "theme-light-bg";
    },

    logo_src() {
      return require("@/static/logo.png");
      if (this.$vuetify.theme.dark) return require("@/static/logo.png");
      else return require("@/static/black.jpeg");
    },

    isDark() {
      return this.currentTheme === "dark";
    },
    appBarColor() {
      console.log(
        this.$vuetify.theme.dark,
        this.$vuetify.theme.themes.dark.surface,
        this.$vuetify.theme.themes.light.surface
      );

      return this.$vuetify.theme.dark
        ? "#1a2632"
        : this.$vuetify.theme.themes.light.surface;
    },

    changeColor() {
      // return "#121653"; //background color dark blue
      // return "#ecf0f4"; //this.$store.state.color;
    },

    getUser() {
      const user = this.$auth.user;
      const userType = user.user_type;

      if (userType === "master") {
        return user.name;
      } else if (userType === "company") {
        return user.company.name;
      }

      const employee = user.employee;
      if (employee) {
        return employee.display_name || employee.first_name;
      }

      return null; // Or some default value indicating no user found
    },

    getLogo() {
      let logosrc = "/no-image.PNG";

      if (
        this.$auth.user &&
        this.$auth.user.user_type == "company" &&
        this.$auth.user.company.logo
      ) {
        logosrc = this.$auth.user.company.logo || "/no-image.PNG1111111";
      } else if (this.$auth.user && this.$auth.user.user_type == "master") {
        logosrc = "/no-image.PNG";
      } else if (this.$auth.user && this.$auth.user.user_type == "employee") {
        logosrc =
          this.$auth.user.employee.profile_picture || "/no-profile-image.jpg";
      } else if (this.$auth.user && this.$auth.user.user_type == "branch") {
        logosrc = this.$auth.user.branch_logo || "/no-profile-image.jpg";
      }

      return logosrc;
    },
    getLoginType() {
      return this.$store.state.loginType;
    },

    hasDepartments() {
      return this.$auth.user && this.$auth.user.assignedDepartments.length > 0;
    },
  },

  methods: {
    async loadEnv() {
      // Load runtime env from store
      try {
        const res = await this.$axios.get("/envsettings");

        this.$store.commit("SET_ENV", res.data);



      } catch (e) {
        console.error("Failed to load env settings", e);

      }
    },
    // layoutMethod() {
    //   alert("");
    // },
    // isDark() {
    //   return this.currentTheme === "dark";
    // },
    // appBarColor() {

    //   return "red";
    //   return this.$vuetify.theme.dark
    //     ? this.$vuetify.theme.themes.dark.primary
    //     : this.$vuetify.theme.themes.light.primary;
    // },

    hidepopupfor5Minutes() {
      this.snackNotification = true;
      this.snackNotificationText = "Popup is closed and Re-open in few minutes";
      this.popupNotificationHide5MinutesActive = true;
      setTimeout(() => {
        this.popupNotificationHide5MinutesActive = false;
      }, 1000 * 60 * 5);
    },
    palysound() {
      this.audio = new Audio(
        this.$store.state.env.BACKEND_URL2 + "alarm_sounds/alarm-sound1.mp3"
      );

      this.playAudioOnUserInteraction();
      // document.addEventListener("click", this.playAudioOnUserInteraction);
      // var elem = this.$refs.myBtn;
      // elem.click();
      ////////// this.$refs.myBtn.click();
      ////var prevented = elem.dispatchEvent(new Event("change")); // Fire event
    },
    playAudioOnUserInteraction() {
      this.audio.play();
    },
    stopsound() {
      if (this.audio) this.audio.pause();
    },

    toggleTheme222222222() {
      this.currentTheme = this.currentTheme === "light" ? "dark" : "light";
      this.$vuetify.theme.themes.light = this.themes[this.currentTheme];
    },
    udapteTheme() {
      // this.update_contact();
      // this.update_geographic();

      let payload = new FormData();

      payload.append("theme", this.$vuetify.theme.dark ? "dark" : "light");

      try {
        this.$axios
          .post(
            `/company/${this.$auth.user.company_id}/update_settings`,
            payload
          )

          .catch((e) => console.log(e));
      } catch (e) { }
    },
    toggleTheme() {
      console.log(this.$vuetify.theme.themes.dark);

      this.$vuetify.theme.dark = !this.$vuetify.theme.dark;

      this.udapteTheme();
      // return false;
      // const isCurrentlyDark = this.$vuetify.theme.dark;

      // if (isCurrentlyDark) {
      //   // Switching to Light Theme
      //   this.$vuetify.theme.themes.light = {
      //     primary: "#6946dd",
      //     accent: "#d8363a",
      //     secondary: "#242424",
      //     background: "#34444c",
      //     main_bg: "#ECF0F4",
      //     violet: "#6946dd",
      //     popup_background: "#ecf0f4",
      //   };
      // } else {
      //   // Switching to Dark Theme
      //   // this.$vuetify.theme.themes.dark = {
      //   //   background: "#1a2632",
      //   //   main_bg: "#101044",
      //   // };
      // }

      // // Now toggle the theme
      // this.$vuetify.theme.dark = !isCurrentlyDark;
    },
    toggleTheme111() {
      this.$vuetify.theme.dark = !this.$vuetify.theme.dark;

      // if (!this.$vuetify.theme.dark) {
      //   this.$vuetify.theme.themes.light = {
      //     primary: "#6946dd", //violoet
      //     accent: "#d8363a",
      //     secondary: "#242424",
      //     background: "#34444c",
      //     main_bg: "#ECF0F4",
      //     violet: "#6946dd",
      //     popup_background: "#ecf0f4",
      //   };
      // } else {
      //   // this.$vuetify.theme.themes.dark = {
      //   //   primary: "#6946dd", //violoet
      //   //   accent: "#d8363a",
      //   //   secondary: "#242424",
      //   //   background: "#34444c",
      //   //   main_bg: "#ECF0F4",
      //   //   violet: "#6946dd",
      //   //   popup_background: "#ecf0f4",
      //   // };
      // }
    },
    updateTopmenu() {
      //update company Top menu
      //filter Display Modules From Company Settings

      try {
        if (this.$auth.user.company.display_modules) {
          let display_modules = JSON.parse(
            this.$auth.user.company.display_modules
          );
          if (display_modules) {
            if (display_modules.access_control == false) {
              this.company_top_menu = this.company_top_menu.filter(
                (item) => item.menu != "access_control"
              );
            }
            if (display_modules.visitors == false) {
              this.company_top_menu = this.company_top_menu.filter(
                (item) => item.menu != "visitors"
              );
            }
            if (display_modules.community == false) {
              this.company_top_menu = this.company_top_menu.filter(
                (item) => item.menu != "community"
              );
            }
          }
        }
      } catch (e) { }
    },
    handleActivity() {
      this.resetTimer();
    },
    resetTimer() {
      // Time in milliseconds after which the user is considered inactive
      const INACTIVITY_TIME = 1000 * 60 * 30; // 5 minutes
      clearTimeout(this.inactivityTimeout);
      this.inactivityTimeout = setTimeout(
        this.handleInactivity,
        INACTIVITY_TIME
      );
    },
    setupInactivityDetection() {
      // Handle inactivity
      this.handleInactivity = () => {
        // Perform actions when the user is inactive
        //this.$router.push(`/dashboard`);
        // For example, you could redirect the user, show a message, etc.
      };

      // Attach event listeners
      window.addEventListener("mousemove", this.handleActivity);
      window.addEventListener("mousedown", this.handleActivity);
      window.addEventListener("keypress", this.handleActivity);
      window.addEventListener("touchmove", this.handleActivity);

      // Initialize the timer
      this.resetTimer();
    },
    gotoHomePage() {
      //location.href = process.env.APP_URL + "/dashboard2";
      location.href = "/alarm/dashboard"; // process.env.APP_URL + "/dashboard2";
    },
    gotoHomePage2() {
      //location.href = process.env.APP_URL + "/dashboard2";
      location.href = "/alarm/dashboard2"; // process.env.APP_URL + "/dashboard2";
    },
    loadNotificationMenu() {
      let company_id = this.$auth.user?.company?.id || 0;
      //console.log("company_id", company_id);
      if (company_id == 0) {
        return false;
      }
      let options = {
        params: {
          company_id: company_id,
        },
      };
      //this.pendingNotificationsCount = 0;
      let pendingcount = 0;
      this.$axios.get(`get-notifications-count`, options).then(({ data }) => {
        this.notificationsMenuItems = [
          {
            title: "Leaves Pending (0)",
            click: "/leaves",
            icon: "mdi-calendar-account",
            key: "leaves",
          },
          {
            title: "Visitors Pending (0)",
            click: "/visitor/requests",
            icon: "mdi-transit-transfer",
            key: "visitors",
          },
        ];
        pendingcount = 0;

        if (data.employee_leaves_pending_count) {
          pendingcount += data.employee_leaves_pending_count;
          let leaves = this.notificationsMenuItems.find(
            (e) => e.key == "leaves"
          );
          leaves.title =
            "Leaves Pending (" + data.employee_leaves_pending_count + ")";
        }
        if (data.visitor_request_pending_count) {
          pendingcount += data.visitor_request_pending_count;
          let leaves = this.notificationsMenuItems.find(
            (e) => e.key == "visitors"
          );
          leaves.title =
            "Visitors Pending (" + data.visitor_request_pending_count + ")";
        }

        this.pendingNotificationsCount = pendingcount;
        // console.log("pendingcount", pendingcount);
        // if (pendingcount == 0) {
        //   this.pendingNotificationsCount = 0;
        // } else this.pendingNotificationsCount = pendingcount;
        // console.log(
        //   "pendingcount",
        //   pendingcount,
        //   this.pendingNotificationsCount
        // );
        // if (this.pendingNotificationsCount == "") {
        //   this.pendingNotificationsCount = 0;
        // }
        // if (this.pendingNotificationsCount > 0) {
        //   //console.log("this.$config", this.$config);
        //   document.title =
        //     "Mytime2Cloud " +
        //     " - Notifications Pending : " +
        //     this.pendingNotificationsCount;
        // }

        // let menu1 = { title: "Leave Notifications (2)", click: "Test" };
        // let menu2 = { title: "Visitor Notifications (2)", click: "Test" };
        // this.notificationsMenuItems.push(menu1);
        // this.notificationsMenuItems.push(menu2);
      });
    },
    openNotificationPopup() {
      this.alarmNotificationStatus = true;
    },
    verifyAlarmStatus() {

      return false;
      let company_id = this.$auth.user?.company?.id || 0;
      //console.log("company_id", company_id);
      if (company_id == 0) {
        return false;
      }
      let options = {
        params: {
          company_id: company_id,
        },
      };
      //this.pendingNotificationsCount = 0;
      let pendingcount = 0;
      this.timmerStatus = false;
      this.$axios
        .get(`get_notifications_alarm_active`, options)
        .then(({ data }) => {
          this.pendingNotificationsCount = data.length;
          if (data.length > 0) {
            this.notificationAlarmDevices = data;
            if (this.$route.name == "alarm-dashboard") {
              if (!this.popupNotificationHide5MinutesActive)
                this.alarmNotificationStatus = true;
            }

            this.palysound();
          } else {
            this.alarmNotificationStatus = false;

            this.stopsound();
          }

          this.timmerStatus = true;
        });
    },

    getBranchName() {
      return this.$auth.user.branch_name;
    },
    getTopMenuItems(i) {
      if (i.module == "dashboard") {
        this.setSubLeftMenuItems(i.submenu, i.to);
      }
    },
    goToPage(page) {
      this.$router.push(page);
    },
    goToSettings() {
      this.setSubLeftMenuItems("settings", "/settings");
    },
    setSubLeftMenuItems(menu_name, page, redirect = true) {
      this.topMenu_Selected = menu_name;
      // console.log(
      //   "this.menuProperties.hasOwnProperty(menu_name)",
      //   this.menuProperties.hasOwnProperty(menu_name)
      // );
      let bgColor = "violet";
      this.setMenus();
      console.log("menu_name", menu_name);

      if (this.menuProperties.hasOwnProperty(menu_name)) {
        for (const key in this.menuProperties) {
          this.menuProperties[key].elevation = 0;
          this.menuProperties[key].selected = "";
        }

        this.menuProperties[menu_name].elevation = 0;
        this.menuProperties[menu_name].selected = bgColor;
      }
      // Check if menu_name exists in menuProperties
      if (this.menuProperties.hasOwnProperty(menu_name)) {
        for (const key in this.menuProperties) {
          this.menuProperties[key].elevation = 0;
          this.menuProperties[key].selected = "";
        }

        this.menuProperties[menu_name].elevation = 0;
        this.menuProperties[menu_name].selected = bgColor;
      }
      if (redirect) return this.$router.push(page);
    },

    setMenus() {
      if (!this.$auth.user.role) {
        {
          //alert("Invalid User Type");
          this.logout();
        }

        return "";
      }
      if (!this.$auth.user.role || this.$auth.user.role.role_type == 0) {
        {
          //alert("Invalid User Type");
          this.logout();
        }

        return "";
      }
      let roleType = this.$auth.user.role.role_type.toLowerCase();

      if (this.getLoginType === "company" || this.getLoginType === "branch") {
        // this.items = this.company_menus;
        this.items = this.company_menus.filter(
          (item) => item.module === this.topMenu_Selected
        );

        // display_modules.forEach((element) => {
        //   console.log("element", element);
        // });

        return;
      } else if (this.getLoginType === "employee") {
        if (/guard/.test(roleType)) {
          this.items = this.guard_menus;
          return;
        } else if (/host/.test(roleType)) {
          this.items = this.host_menus;
          return;
        } else {
          this.items = this.company_menus.filter(
            (item) => item.module === this.topMenu_Selected
          );
          return;
        }
      }
    },

    changeLoginType() {
      try {
        // if (this.getLoginType == "branch")
        {
          // this.$store.commit("loginType", "employee");
          // this.setMenus();
          let email = this.$store.state.email;
          let password = this.$store.state.password;

          email = this.$crypto.encrypt(email);
          password = this.$crypto.encrypt(password);

          email = encodeURIComponent(email);
          password = encodeURIComponent(password);

          if (email && password) {
            window.location.href =
              process.env.EMPLOYEE_APP_URL +
              "/loginwithtoken?email=" +
              email +
              "&password=" +
              password;

            return "";
          } else {
            console.log("Empty Username and Password");
          }
          // this.$router.push("/employees/profile");
        }
        // else {
        //   this.$store.commit("loginType", "branch");
        //   this.setMenus();
        //   this.$router.push("/dashboard2");
        // }
      } catch (e) {
        console.log(e);
      }
    },
    navigateToLeavePage() {
      this.$router.push("/leaves");
    },

    filterBranch(branch) {
      this.$emit("openalert", "");

      if (branch) {
        this.selectedBranchName = branch.branch_name;
        this.seelctedBranchId = branch.id;
      } else {
        this.selectedBranchName = "All Branches";
        this.seelctedBranchId = "";
      }
    },
    collapseSubItems() {
      this.company_menus.map((item) => (item.active = false));
    },
    changeTopBarColor(color) {
      this.color = color;
      this.$store.commit("change_color", color);
    },

    changeTheme(color) {
      alert(color);
    },

    changeSideBarColor(color) {
      this.sideBarcolor = color;
    },

    caps(str) {
      return str.replace(/\b\w/g, (c) => c.toUpperCase());
    },
    goToSetting() {
      this.$router.push("/setting");
    },
    goToLeaves() {
      this.$router.push("/leaves");
    },
    goToCompany() {
      this.$router.push(`/companies/${this.$auth.user?.company_id}`);
    },
    getCompanyDetails() {
      this.$axios
        .get(`company/${this.$auth.user?.company_id}`)
        .then(({ data }) => {
          let { modules } = data.record;

          if (modules !== null) {
            this.modules = {
              module_ids: modules.module_ids || [],
              module_names: modules.module_names.map((e) => ({
                icon: "mdi-chart-bubble",
                title: this.caps(e),
                to: "/" + e + "_modules",
                permission: true,
              })),
            };
          }
        });
    },
    can(per) {
      return this.$pagePermission.can(per, this);
    },

    logout() {
      this.$axios.get(`/logout`).then(({ res }) => {
        this.$auth.logout();
      });
    },
  },
  beforeDestroy() {
    // Cleanup: Remove event listeners
    window.removeEventListener("mousemove", this.handleActivity);
    window.removeEventListener("mousedown", this.handleActivity);
    window.removeEventListener("keypress", this.handleActivity);
    window.removeEventListener("touchmove", this.handleActivity);
    clearTimeout(this.inactivityTimeout);
  },
};
</script>
<style scoped>
.btn-text-size {
  font-size: 15px !important;
}

.leftMenuWidth {
  width: 140px !important;
}

.main_bg {
  padding-left: 140px;
}

.v-list-item {
  text-align: center;
  width: 100%;
}
</style>
<!-- Extra overriting classes-->
<style scoped>
.violet {
  background-color: #6946dd;
}

.bold {
  color: bold;
}

.text--violet {
  color: #6946dd;
}

.v-list-item--active i {
  color: #6946dd;
}

/* header,
header button,
header a,
header i {
  color: black !important;
} */
.header-devices-menu-selected {
  color: #fff !important;
}

.header-devices-menu {
  color: red !important;
}

/*
.theme--dark.v-bottom-navigation .v-btn:not(.v-btn--active) {

  color: red !important;
}
.v-item-group.v-bottom-navigation .v-btn .v-btn__content > *:not(.v-icon) {
  color: red !important;
}
.header-devices-menu-selected span {
  color: red !important;
}
.header-devices-menu-selected .v-btn__content span {
  color: orange !important;
}

.header-devices-menu .v-btn--active {
  color: red !important;
}
.header-devices-menu {
  color: green !important;
}
.theme--light.v-bottom-navigation .v-btn:not(.v-btn--active) {
  color: yellow !important;
}
.theme--light.v-bottom-navigation .v-btn--active {
  color: yellow !important;
}*/
</style>

<style>
.violet {
  background-color: #6946dd;
}

.bold {
  font-weight: bold;
}

.text--violet {
  color: #6946dd;
}

.view-profile-table-lineheight {
  line-height: 40px;
  width: 100%;
}

.view-profile-table-lineheight tr {
  border-bottom: 1px solid #474141;
}

.view-profile-table-lineheight td {
  padding-right: 5px;
  padding-left: 5px;
}

.whitebackground--text {
  background-color: #ecf0f4;
}

/* New Theme  popup_background*/
.v-application .popup_background {
  background-color: #ecf0f4 !important;
}

.popup_background {
  background-color: #ecf0f4 !important;
  color: black !important;
  font-weight: 400 !important;

  font-size: 1.25rem !important;
}

.popup_title,
.v-dialog>.v-card>.v-card__title {
  color: black !important;
  font-weight: 400 !important;

  font-size: 1.25rem !important;
}

.popup_background i {
  color: black !important;
}

.popup_background button {
  color: black !important;
}

.popup_background .v-tabs-bar {
  background-color: #ecf0f4 !important;
  color: black;
  font-weight: 400;

  font-size: 1.25rem;
}

.popup_background .v-tabs-bar i {
  color: black !important;
}

.popup_background .v-toolbar__title {
  color: black !important;
}

.popup_background .v-icon {
  color: black !important;
}

/* .theme--dark.v-toolbar.v-sheet {
  background-color: #cfd8dc !important;
} */
/* .v-card {
  background-color: #cfd8dc;
}
.v-card header {
  background-color: #cfd8dc;
}
.v-card .v-card__title {
  color: black;
}
.v-card i {
  color: black;
}

.v-card .v-toolbar__title {
  color: black;
} */

.input-small-fieldset fieldset {
  height: 30px;
}

.input-small-fieldset input {
  margin-top: -15px;
}

.input-small-fieldset .v-input__append-inner {
  margin-top: -2px;
}

.black--text {
  color: black;
}

.black {
  color: black;
}

.iconsize {
  width: 20px;
}

.secondary-value {
  font-size: 10px;
}

.form-control:focus {
  box-shadow: none !important;

  /* height: 32px !important; */
}

.iconsize30 {
  width: 30px;
  height: auto;
}

.basic-table-design {
  tr {
    width: 100%;
    border-bottom: 1px solid #474141;
  }
}

/* .v-application .primary--text {
  color: #6946dd !important;
  caret-color: #6946dd !important;
} */

.slidegroup1 .v-slide-group {
  height: 34px !important;
}
</style>

<style>
/*! CSS Used from: https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/3.10.2/mdb.min.css */
*,
:after,
:before {
  box-sizing: border-box;
}

button {
  border-radius: 0;
}

button:focus:not(:focus-visible) {
  outline: 0;
}

button {
  margin: 0;
  font-family: inherit;
  font-size: inherit;
  line-height: inherit;
}

button {
  text-transform: none;
}

[type="button"],
button {
  -webkit-appearance: button;
}

[type="button"]:not(:disabled),
button:not(:disabled) {
  cursor: pointer;
}

.btn {
  display: inline-block;
  color: #4f4f4f;
  text-align: center;
  text-decoration: none;
  vertical-align: middle;
  cursor: pointer;
  -webkit-user-select: none;
  -moz-user-select: none;
  user-select: none;
  background-color: transparent;
  border: 0.125rem solid transparent;
  padding: 0.375rem 0.75rem;
  border-radius: 0.25rem;
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out,
    border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

@media (prefers-reduced-motion: reduce) {
  .btn {
    transition: none;
  }
}

.btn:hover {
  color: #4f4f4f;
}

.btn:disabled {
  pointer-events: none;
  opacity: 0.65;
}

button:focus {
  outline: 0;
}

.btn {
  text-transform: uppercase;
  vertical-align: bottom;
  border: 0;
  box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.2), 0 2px 10px 0 rgba(0, 0, 0, 0.1);
  font-weight: 500;
  padding: 0.625rem 1.5rem 0.5rem;
  font-size: 0.75rem;
  line-height: 1.5;
}

.btn:active,
.btn:active:focus,
.btn:focus,
.btn:hover {
  box-shadow: 0 4px 10px 0 rgba(0, 0, 0, 0.2), 0 4px 20px 0 rgba(0, 0, 0, 0.1);
}

.btn:disabled {
  box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.2), 0 2px 10px 0 rgba(0, 0, 0, 0.1);
  border: 0;
}

.btn:focus {
  outline: 0;
  box-shadow: 0 4px 10px 0 rgba(0, 0, 0, 0.2), 0 4px 20px 0 rgba(0, 0, 0, 0.1);
}

.btn-block {
  display: block;
  width: 100%;
}

.btn-block+.btn-block {
  margin-top: 0.5rem;
}

/*! CSS Used from: https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/4.4.95/css/materialdesignicons.min.css */
.mdi:before {
  display: inline-block;
  font: normal normal normal 24px/1 "Material Design Icons";
  font-size: inherit;
  text-rendering: auto;
  line-height: inherit;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.mdi-chevron-right::before {
  content: "\F142";
}

/*! CSS Used from: https://cdn.jsdelivr.net/npm/@mdi/font@latest/css/materialdesignicons.min.css */
.mdi:before {
  display: inline-block;
  font: normal normal normal 24px/1 "Material Design Icons";
  font-size: inherit;
  text-rendering: auto;
  line-height: inherit;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.mdi-chevron-right::before {
  content: "\F0142";
}

/*! CSS Used from: Embedded */
*,
:after,
:before {
  background-repeat: no-repeat;
  box-sizing: inherit;
}

:after,
:before {
  text-decoration: inherit;
  vertical-align: inherit;
}

* {
  margin: 0;
  padding: 0;
}

button {
  font: inherit;
}

button {
  overflow: visible;
}

button {
  text-transform: none;
}

[type="button"],
button {
  color: inherit;
  cursor: pointer;
}

button,
html [type="button"] {
  -webkit-appearance: button;
}

button {
  background-color: transparent;
  border-style: none;
}

.v-icon.v-icon {
  font-feature-settings: "liga";
  align-items: center;
  display: inline-flex;
  font-size: 24px;
  justify-content: center;
  letter-spacing: normal;
  line-height: 1;
  position: relative;
  text-indent: 0;
  transition: 0.3s cubic-bezier(0.25, 0.8, 0.5, 1), visibility 0s;
  -webkit-user-select: none;
  -moz-user-select: none;
  user-select: none;
  vertical-align: middle;
}

.v-icon.v-icon:after {
  background-color: currentColor;
  border-radius: 50%;
  content: "";
  display: inline-block;
  height: 100%;
  left: 0;
  opacity: 0;
  pointer-events: none;
  position: absolute;
  top: 0;
  transform: scale(1.3);
  transition: opacity 0.2s cubic-bezier(0.4, 0, 0.6, 1);
  width: 100%;
}

.apexcharts-menu-icon {
  padding-top: 10px;
}
</style>
<style>
.v-application .error--text {
  color: red;
}

.error--text,
.text-danger {
  font-size: 13px;
  color: red !important;
}

.v-messages {
  min-height: 0px !important;
}

.text-break-dot {
  text-overflow: ellipsis;
  width: 200px;
  overflow: hidden;
  white-space: nowrap;
  height: 25px;
}

.breakthewords {
  display: -webkit-box;
  font-size: 12px !important;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.branchlogo {
  width: 50px;
}

.table_active {
  background: #e1e1e1;
  border-left: 1px solid green;
  font-weight: bold;
}

.annnouncment_table .text-left {
  padding: 0px !important;
}

.announ_priority {
  color: rgb(63, 81, 181);
  width: 100%;
  text-align: center;
  padding-bottom: 10px;
  font-weight: bold;
  font-size: 19px;
}

.slidegroup1 .v-slide-group {
  height: 34px !important;
}

.icon-blue {
  color: #755fc9 !important;
}

.company_logo .v-image__image--cover {
  background-size: contain;
}

.no-border>.v-input__control>.v-input__slot:before {
  border-color: #fff !important;
}

/* .no-border:before {
  border-color: #fff !important;
} */

.logtable .v-data-table__wrapper {
  height: 670px;
  overflow-y: scroll;
}

.logtable-comm .v-data-table__wrapper {
  height: 830px;
  overflow-y: scroll;
}

.alarm {
  animation: changeBackgroundColor 1s infinite;
}

@keyframes changeBackgroundColor {
  0% {
    color: #000000;
  }

  10% {
    color: #f73030;
  }

  20% {
    color: #000000;
  }

  40% {
    color: #f73030;
  }

  60% {
    color: #000000;
  }

  80% {
    color: #fc0000;
  }

  90% {
    color: #000000;
  }

  100% {
    color: #fc0000;
  }
}
</style>
<!-- <style>
body {
  min-width: 1600px;
}
</style> -->

<style>
#app {
  width: 100%;
  height: 100%;
}

body {
  min-width: 400px !important;
}

.apexcharts-canvas {
  width: 100%;
}
</style>

<style>
.theme--light.v-text-field>.v-input__control>.v-input__slot:before {
  border-color: #fff !important;
}

.no-border:before {
  border-color: #fff !important;
}
</style>

<style>
.theme--light.v-text-field>.v-input__control>.v-input__slot:before {
  border-color: #fff !important;
}

.no-border:before {
  border-color: #fff !important;
}

.v-toolbar__content .mdi-menu {
  color: #fff !important;
}

.apexcharts-text .apexcharts-yaxis-title-text,
.apexcharts-yaxis-title-text,
.apexcharts-title-text {
  font-weight: normal !important;
  font-family: "Roboto", sans-serif !important;
  color: black !important;
  font-size: 16px !important;
  padding-top: 5px;
}

.apexcharts-text {
  font-family: "Roboto", sans-serif !important;
}

/* .v-application .main_bg_alarm-dashboard2 {
  background-color: #101044 !important;
} */

.dashboard {
  display: flex;
  gap: 20px;
  background-color: #101044;
  padding: 20px;
}

.daterange-blacktheme .mx-input {
  background: #1a2632;
  color: #fff !important;
}

.daterange-blacktheme .mx-icon-calendar {
  color: #fff !important;
}

.app-header-main {
  margin: 12px !important;
  margin-bottom: 0px !important;
  /* margin: 16px !important; */
  border-radius: 10px !important;
  /* z-index: 99999 !important; */
}

.main_bg_header {
  padding-top: 74px !important;
  margin-top: 10px;
}

.container {
  padding-top: 0px !important;
  margin-top: -5px !important;
}

/* .livedateTime span {
  color: #fff !important;
} */
</style>
<style>
.gridWrap {
  display: grid;
  gap: 14px;
}

@media (max-width: 1400px) {
  .gridWrap {
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
  }
}

@media (max-width: 960px) {
  .gridWrap {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }
}

@media (max-width: 600px) {
  .gridWrap {
    grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
  }
}

.roomCard {
  border-radius: 12px;

}

.cardOn {
  border: 2px solid rgba(239, 68, 68, 0.95);
  box-shadow: 0 0 18px rgba(239, 68, 68, 0.18);
}

.cardOff {
  border: 1px solid rgba(148, 163, 184, 0.35);
}

.cardAck {

  border: 2px solid #f97316;
}

.sos-border-red {
  border-color: rgba(239, 68, 68, 0.35) !important;
}

.sos-border-orange {
  border-color: #f97316 !important;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  display: inline-block;
}

.dot-red {
  background: #ef4444;
  box-shadow: 0 0 10px rgba(239, 68, 68, .25);
}

.dot-grey {
  background: #64748b;
}

@keyframes blink {
  0% {
    opacity: 1;
  }

  50% {
    opacity: 0.3;
  }

  100% {
    opacity: 1;
  }
}

.blink-btn {
  animation: blink 1s infinite;
}

.stat-card {
  border-radius: 14px;
  border: 1px solid transparent !important;
}

/* ===== Full Border Colors ===== */
.stat-critical {
  border-color: #ef4444 !important;
  background: rgba(239, 68, 68, 0.05);
}

.stat-warning {
  border-color: #f97316 !important;
  background: rgba(249, 115, 22, 0.05);
}

.stat-success {
  border-color: #22c55e !important;
  background: rgba(34, 197, 94, 0.05);
}

.stat-info {
  border-color: #3b82f6 !important;
  background: rgba(59, 130, 246, 0.05);
}

/* ===== Text & Icon Colors ===== */
.critical-text {
  color: #ef4444 !important;
}

.warning-text {
  color: #f97316 !important;
}

.success-text {
  color: #22c55e !important;
}

.info-text {
  color: #3b82f6 !important;
}

/* Typography */
.stat-title {
  letter-spacing: 0.06em;
}

.stat-sub {
  font-weight: 600;
  opacity: 0.9;
}

.stat-icon {
  font-size: 34px;
  opacity: 0.95;
}
</style>
<style scoped>
@import "@../../assets/whitetheme.css";
</style>

<!-- <style scoped>
@import "@../../assets/blacktheme.css";
</style> -->
<style>
@import "@../../assets/mobile.css";
</style>
<style>
@import "@../../assets/darktheme.css";
</style>
<style>
@import "@../../assets/darkchart.css";
</style>
<!-- BLACK THEME ----------------------------------------------START --------------------------------------------------------------------->

<!-- <link rel="stylesheet" href="../static/css/textbox-label-style.css" /> -->
<!-- <link rel="stylesheet" href="@/assets/blacktheme.css" /> -->
