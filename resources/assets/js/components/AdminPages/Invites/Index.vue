<template lang="html">
  <div class='col-xs-12'>
    <div class='page-header page-header-with-buttons'>
      <h1 class='pull-left'>
        <i class="fa fa-envelope"></i>
        Всички покани
      </h1>

      <div class='pull-right' v-if="can('create-invitation')">
        <div class='btn-group'>
          <router-link class="btn btn-success" :to="{ path: 'invite/create' }">
            <i class='fa fa-plus'></i>
            Създай покана
          </router-link>
        </div>
      </div>
    </div>

    <alert :alert="alert" v-if="hasAlert"></alert>

    <div class='box bordered-box' style='margin-bottom:0;'>
      <div class='box-content'>
        <div class="responsive-table">
          <table class=' table table-bordered table-hover table-striped' style='margin-bottom:0;'>
            <thead>
              <tr>
                <th>
                  Поканата
                </th>
                <th>
                  E-mail
                </th>
                <th v-if="can('delete-invitation')">
                  Опции
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="invite in invites">
                <td>{{ hasInvite(invite) }}</td>
                <td>{{ invite.email }}</td>
                <td v-if="can('delete-invitation')">
                  <div class='text-right'>
                      <button class="btn btn-danger btn-xs" @click="DeleteInvite(invite.id)" v-if="can('delete-invitation')">
                        <i class="fa fa-remove"></i>
                        <span>Изтрий</span>
                      </button>
                      <router-link
                        tag="a"
                        class="btn btn-success btn-xs"
                        :to="{ name:'CreateInvite', params:{ id: invite.id }}"
                        v-if="!hasInvite(invite) && can('send-invitation')"
                      >
                        <i class='fa fa-mail-forward'></i>
                        <span>Изпрати покана</span>
                      </router-link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Alert from "../../Alert.vue"
export default {
  data () {
    return {
      invites: [],
      invitesIds: [],
      hasAlert: false,
      alert: {},
    }
  },

  components: {
    "alert": Alert
  },

  beforeCreate () {
    this.$parent.isLoading = true
    this.$http.get('/api/invite').then(
      (response) => {
        this.invites = response.data
        this.invitesIds = response.data.map(el => el.id)

        this.$parent.setDataTable()

        this.$parent.isLoading = false
      }, (error) => {
        console.log(error);
      }
    )
  },

  computed:{
    isAdmin(){
      return this.$store.getters.User.role == 'admin'
    }
  },

  methods: {
    DeleteInvite(id) {
      this.hasAlert = false
      this.$parent.isLoading = true

      this.$http.delete('/api/invite/' + id).then((response) => {
        this.hasAlert = true

        this.alert = {
          type: 'alert-success',
          messages: response.data.success
        }

        const index = this.invitesIds.indexOf(id)
        this.$parent.table.fnDestroy()

        this.invites.splice(index, 1)
        this.invitesIds.splice(index, 1)

        this.$parent.setDataTable()

        this.$parent.isLoading = false
      }, console.error)
    },

    hasInvite(i) {
      return (i.invite !== null) ? i.invite : 'Няма изпратена покана'
    }
  }
}
</script>
