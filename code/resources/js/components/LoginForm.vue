<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="7" md="4" lg="3">

        <!-- Logo + titolo -->
        <div class="text-center mb-8">
          <v-icon size="60" color="primary">mdi-calendar-clock</v-icon>
          <div class="text-h5 font-weight-medium mt-3">Gestione Turni</div>
        </div>

        <!-- Card login -->
        <v-card rounded="xl" elevation="2" class="pa-2">
          <v-card-text class="pa-6">
            <div class="text-h6 mb-6">Accedi al tuo account</div>

            <v-alert
              v-if="hasErrors"
              type="error"
              variant="tonal"
              density="compact"
              class="mb-5"
            >{{ errorMessage }}</v-alert>

            <form ref="loginForm" method="POST" :action="loginRoute">
              <input type="hidden" name="_token" :value="csrfToken">

              <v-text-field
                label="Email"
                type="email"
                name="email"
                v-model="email"
                prepend-inner-icon="mdi-email-outline"
                class="mb-4"
                autofocus
              ></v-text-field>

              <v-text-field
                label="Password"
                :type="showPassword ? 'text' : 'password'"
                name="password"
                v-model="password"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"
                @click:append-inner="showPassword = !showPassword"
                class="mb-3"
              ></v-text-field>

              <div class="d-flex align-center mb-5">
                <input
                  type="checkbox"
                  name="remember"
                  id="remember"
                  value="1"
                  v-model="remember"
                  style="width:16px; height:16px; accent-color: var(--v-theme-primary); cursor:pointer;"
                >
                <label for="remember" class="ml-2 text-body-2" style="cursor:pointer;">Ricordami</label>
              </div>

              <v-btn
                type="submit"
                color="primary"
                rounded="lg"
                block
                size="large"
                elevation="0"
              >
                Accedi
              </v-btn>
            </form>
          </v-card-text>
        </v-card>

      </v-col>
    </v-row>
  </v-container>
</template>

<script>
export default {
    props: {
        loginRoute:   { type: String, required: true },
        csrfToken:    { type: String, required: true },
        oldEmail:     { type: String, default: '' },
        hasErrors:    { type: Boolean, default: false },
        errorMessage: { type: String, default: '' },
    },
    data() {
        return {
            email:        this.oldEmail,
            password:     '',
            remember:     false,
            showPassword: false,
        };
    },
};
</script>
