<div id="app" v-cloak>
<v-app style="background:transparent">
    <v-main>
    <v-container>
    <v-card style="max-width: 600px; margin: 0px auto 20px auto;position:relative" class="pa-3">
    <v-card-text>
    <v-row>
        <v-col cols="12" class="py-0">
          <v-text-field v-model="site_url" variant="underlined" spellcheck="false" label="Site URL" hide-details @paste.prevent="checkUrl"></v-text-field>
        </v-col>
        <v-col cols="12" class="py-0">
            <v-text-field label="Token" variant="underlined" spellcheck="false" v-show="use_token" v-model="token" class="token">
                <template v-slot:append>
                    <a href="https://github.com/DisembarkHost/disembark-connector/releases/latest/download/disembark-connector.zip" target="_blank">
                        <v-icon @click.stop v-on="on">mdi-open-in-new</v-icon>
                        <v-tooltip location="top" activator="parent" text="Download Disembark Connector required for token connection."></v-tooltip>
                    </a>
                </template>
            </v-text-field>
        </v-col>
        <v-col cols="12" sm="6" md="6" class="py-0" v-show="! use_token">
          <v-text-field label="Username" spellcheck="false"></v-text-field>
        </v-col>
        <v-col cols="12" sm="6" md="6" class="py-0" v-show="! use_token">
          <v-text-field type="password" label="Password" spellcheck="false"></v-text-field>
        </v-col>
        <v-col cols="12" class="py-0">
        <v-row>
            <v-col cols="12" sm="4" md="4">
                <v-switch v-model="advanced" density="compact" inset :ripple="false" label="Options" @change="advanced == true && connect()"></v-switch>
            </v-col>
            <v-col cols="12" sm="4" md="4" v-show="advanced">
                <v-switch v-model="options.database" density="compact" inset :ripple="false" label="Database"></v-switch>
            </v-col>
            <v-col cols="12" sm="4" md="4" v-show="advanced">
                <v-switch v-model="options.files" density="compact" inset :ripple="false" label="Files"></v-switch>
            </v-col>
            <v-col cols="12" sm="12" md="12" v-show="advanced && ! options.files" class="pt-0 mt-0">
                <v-text-field v-model="options.include_files" variant="underlined" label="Include files or paths" hint="Comma separated list of files or paths to include" persistent-hint spellcheck="false" class="pt-0 mt-0"></v-text-field>
            </v-col>
            <v-col cols="12" sm="12" md="12" v-show="advanced && ! options.database" class="pt-0 mt-0">
                <v-text-field v-model="options.include_database_tables" variant="underlined" label="Include database tables" hint="Comma separated list of database tables to include" persistent-hint spellcheck="false" class="pt-0 mt-0"></v-text-field>
            </v-col>
        </v-row>
        </v-col>
        <v-col cols="12">
            <v-btn block color="var(--theme-palette-color-4)" class="text-white" @click="connect( true )">Begin Backup Snapshot <v-icon class="ml-2">mdi-cloud-download</v-icon></v-btn>
        </v-col>
    </v-row>
    </v-card-text>
    <v-overlay v-model="loading" opacity="0.7" contained class="align-center justify-center">
        <div class="text-center text-white text-body-1">
            <div><strong>Backup in progress...</strong></div>
            <v-progress-circular indeterminate color="white" class="my-5" :size="32" :width="2"></v-progress-circular>
            <div>Refreshing this page will cancel the current backup.</div>
        </div>
    </v-overlay>
    </v-card>
    <div style="opacity:0;"><textarea id="clipboard" style="height:1px;width:10px;display:flex;cursor:default"></textarea></div>
    <v-alert variant="outlined" type="success" text v-if="migrateCommand" class="mb-4">
      Backup is ready. You can generate a zip file locally use the following commands in your terminal.
        <pre style="font-size: 11px;color: var(--theme-palette-color-1);margin: 14px 14px 0px 0px;background: var(--theme-palette-color-2);">{{ migrateCommand }}</pre>
        <div style="position:relative">
            <v-btn variant="text" icon="mdi-content-copy" @click="copyText( migrateCommand )" position="absolute" style="bottom: 0px;right: 12px;" class="mr-2" color="var(--theme-palette-color-1)"></v-btn>
        </div>
    </v-alert>
    <v-row>
        <v-col cols="12" sm="12" md="6" v-if="database.length > 0">
        <v-toolbar flat dark density="compact" color="var(--theme-palette-color-2)" class="text-white pr-5">
            <v-toolbar-title>Database</v-toolbar-title>
            <v-spacer></v-spacer>
            {{ formatSize(totalDatabaseSize) }}
        </v-toolbar>
        <v-progress-linear v-model="databaseProgress" color="amber" height="25">
            Copied {{ database_progress.copied }} of {{ database.length }} tables
        </v-progress-linear>
        <v-table density="compact">
            <template v-slot:default>
            <thead>
                <tr>
                <th class="text-left">
                    Name
                </th>
                <th class="text-left">
                    Size
                </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in database" :key="item.table">
                <td><v-icon v-show="item.done" class="mr-2">mdi-check-circle</v-icon>{{ item.table }} <span v-if="item.parts">({{ item.current }}/{{ item.parts }})</span> <v-progress-circular indeterminate color="primary" class="ml-3" :size="20" :width="2" v-show="item.running"></v-progress-circular></td>
                <td>{{ formatSize( item.size ) }}</td>
                </tr>
            </tbody>
            </template>
        </v-table>
        </v-col>
        <v-col cols="12" sm="12" md="6" v-if="files.length > 0">
        <v-toolbar flat dark density="compact" color="var(--theme-palette-color-2)" class="text-white pr-5">
            <v-toolbar-title>Files</v-toolbar-title>
            <v-spacer></v-spacer>
            {{ formatSize( files_total ) }}
        </v-toolbar>
        <v-progress-linear v-model="filesProgress" color="amber" height="25">
        Copied {{ formatLargeNumbers( files_progress.copied ) }} of {{ formatLargeNumbers ( totalFileCount ) }}
        </v-progress-linear>
        </v-col>
    </v-row>
    </v-container>
    <v-snackbar :timeout="3000" :multi-line="true" v-model="snackbar.show" variant="outlined" style="z-index: 9999999;" color="var(--theme-palette-color-2)">
        {{ snackbar.message }}
    </v-snackbar>
    </v-main>
</v-app>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@3.4.30/dist/vue.global.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@v3.6.10/dist/vuetify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios@1.7.2/dist/axios.min.js"></script>
<script>
const { createApp } = Vue;
const { createVuetify } = Vuetify;
const vuetify = createVuetify();

createApp({
    data() {
        return {
            advanced: false,
            options: {
                database: true,
                files: true,
                include_files: "",
                include_database_tables: ""
            },
            site_url: "<?php echo $_GET['disembark_site_url']; ?>",
            token: "<?php echo $_GET['disembark_token']; ?>",
            backup_token: "",
            loading: false,
            snackbar: { show: false, message: "" },
            use_token: true,
            database_progress: { copied: 0, total: 0 },
            files_progress: { copied: 0, total: 0 },
            backup_progress: { copied: 0, total: 0 },
            backup_ready: false,
            database: [],
            files: [],
            files_total: 0
        }
    },
    methods: {
        backupFiles() {
            if ( ! this.options.files && this.options.include_files != "" ) {
                include_files = this.options.include_files.split(",").map(str => str.trim())
                this.backup_progress.total = include_files.length
            }
            if ( this.backup_progress.copied == this.backup_progress.total ) {
                this.loading = false
                this.backup_ready = true
                return
            }
            file = this.files[ this.backup_progress.copied ]
            data = {
                site_url: this.site_url,
                token: this.token,
                backup_token: this.backup_token,
                file: file.name
            }
            if ( ! this.options.files && this.options.include_files != "" ) {
                selected_file = include_files[ this.backup_progress.copied ]
                data = {
                    site_url: this.site_url,
                    token: this.token,
                    backup_token: this.backup_token,
                    include_file: selected_file
                }
            }
            axios.post( '/wp-json/disembark/v1/remote/zip-files', data).then( response => {
                if ( response.data == "" ) {
                    this.snackbar.message = `Could not zip ${file.name}.`
                    this.snackbar.show = true
                    this.loading = false
                    return
                }
                if ( this.options.files ) {
                    this.files_progress.copied = this.files_progress.copied + file.count
                }
                this.backup_progress.copied = this.backup_progress.copied + 1
                this.backupFiles()
            }).catch(error => {
                this.snackbar.message = `Could not zip ${file.name}. Retrying...`
                this.snackbar.show = true
                this.backupFiles()
            })
        },
        backupDatabase() {
            if ( ! this.options.database && this.options.include_database_tables != "" ) {
                include_database_tables = this.options.include_database_tables.split(",").map(str => str.trim())
                this.database_progress.total = include_database_tables.length
            }
            if ( this.database_progress.copied == this.database_progress.total ) {
                skip_database_zip = false
                this.database.forEach( table => {
                    if ( table.parts ) {
                        skip_database_zip = true
                    }
                })
                if ( this.options.files || this.options.include_files != "" ) {
                    this.backupFiles()
                } else {
                    this.loading = false
                    this.backup_ready = true
                }
                if ( skip_database_zip ) {
                    return
                }
                this.zipDatabase()
                return
            }
            table = this.database[ this.database_progress.copied ]
            if ( ! this.options.database && this.options.include_database_tables != "" ) {
                selected_table = include_database_tables[ this.database_progress.copied ]
                this.database.forEach( table_lookup => {
                    if ( table_lookup.table == selected_table ) {
                        table = table_lookup
                    }
                })
            }
            table.running = true
            data = {
                site_url: this.site_url,
                token: this.token,
                backup_token: this.backup_token,
                table: table.table
            }
            if ( table.parts ) {
                table.current = table.current + 1
                data.parts = table.current
                data.rows_per_part = table.rows_per_part
            }
            axios.post( '/wp-json/disembark/v1/remote/export-database', data).then( response => {
                if ( response.data == "" ) {
                    this.snackbar.message = `Could not backup table ${table.table}.`
                    this.snackbar.show = true
                    this.loading = false
                    table.running = false
                    return
                }
                if ( table.parts && table.current != table.parts ) {
                    axios.post( '/wp-json/disembark/v1/remote/zip-database', data).then( response => {
                        table.running = false
                        this.backupDatabase()
                        return
                    })
                }
                this.database_progress.copied = this.database_progress.copied + 1
                table.running = false
                table.done    = true
                this.backupDatabase()
            }).catch(error => {
                this.snackbar.message = `Could not backup table ${table.table}. Retrying...`
                table.current = table.current - 1
                this.snackbar.show = true
                this.backupDatabase()
            })

        },
        zipDatabase() {
            axios.post( '/wp-json/disembark/v1/remote/zip-database', {
                    site_url: this.site_url,
                    token: this.token,
                    backup_token: this.backup_token
                }).then( response => {
                    if ( response.data == "" ) {
                        this.snackbar.message = `Could not database.`
                        this.snackbar.show = true
                        return
                    }
                })
        },
        checkUrl( event ) {
            new_url = event.clipboardData.getData('text').trim()
            if ( new_url.includes( "\n" ) ) {
                // attempt to split
                parts = new_url.split("\n")
                if ( parts.length == 2 ) {
                    this.site_url = parts[0]
                    this.token = parts[1]
                }
                this.connect()
            } else {
                this.site_url = new_url
            }
        },
        connect( backup = false ) {
            this.backup_token = ""
            this.database_progress = { copied: 0, total: 0 }
            this.files_progress = { copied: 0, total: 0 }
            this.backup_progress = { copied: 0, total: 0 }
            this.database = []
            this.files = []
            this.files_total = 0
            if ( this.site_url == "" ) {
                this.snackbar.message = `Please enter a site URL.`
                this.snackbar.show = true
                return
            }
            if ( this.token == "" ) {
                this.snackbar.message = `Please enter a token.`
                this.snackbar.show = true
                return
            }
            if ( ! this.site_url.includes("http://") && ! this.site_url.includes("https://") ) {
                this.site_url = "https://" + this.site_url
            }
            if ( backup != "" ) {
                this.loading = true 
            }
            this.site_url = this.site_url.replace(/\/$/, "")
            this.snackbar.message = `Analyzing ${this.site_url}`
            this.snackbar.show = true
            axios.post( '/wp-json/disembark/v1/remote/connect', {
                site_url: this.site_url,
                token: this.token
            })
            .then( response => {
                if ( response.data == "" || response.data == "404" || response.data == "403" ) {
                    this.snackbar.message = `Could not connect to ${this.site_url}. Verify token or WordPress login.`
                    this.snackbar.show = true
                    this.loading = false
                    return
                }
                if ( response.data.error && response.data.error != "" ) {
                    this.snackbar.message = `Could not connect to ${this.site_url}. ${response.data.error}`
                    this.snackbar.show = true
                    this.loading = false
                    return
                }
                this.database = response.data.database
                this.database_progress.total = this.database.length
                this.backup_token = response.data.token
                this.files = response.data.files
                this.files_progress.total = response.data.files.map( file => file.count ).reduce((partialSum, a) => partialSum + a, 0);
                this.backup_progress.total = response.data.files.length
                this.files_total = response.data.files.map( file => file.size ).reduce((partialSum, a) => partialSum + a, 0);
                if ( backup && ( this.options.database || this.options.include_database_tables != "" ) ) {
                    this.backupDatabase()
                }
                if ( backup && ! this.options.database && this.options.include_database_tables == "" ) {
                    this.backupFiles()
                }
            });
        },
        copyText( value ) {
            var clipboard = document.getElementById("clipboard");
            clipboard.value = value;
            clipboard.focus()
            clipboard.select()
            document.execCommand("copy");
            this.snackbar.message = "Copied to clipboard.";
            this.snackbar.show = true;
        },
        formatSize (fileSizeInBytes) {
            if ( fileSizeInBytes == null || typeof fileSizeInBytes !== 'number' ) {
                return 0;
            }
            var i = -1;
            var byteUnits = [' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
            do {
                fileSizeInBytes = fileSizeInBytes / 1024;
                i++;
            } while (fileSizeInBytes > 1024);
            return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
        },
        formatLargeNumbers (number) {
            if ( isNaN(number) || number == null ) {
                return null;
            } else {
                return number.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
            }
        },
    },
    mounted() {
        this.$vuetify.theme.themes.light.primary = getComputedStyle(document.documentElement).getPropertyValue('--theme-palette-color-2');
        if ( this.site_url != "" && this.token != "" ) {
            this.connect()
        }
    },
    computed: {
        filesProgress() {
            return this.files_progress.copied / this.files_progress.total * 100
        },
        databaseProgress() {
            return this.database_progress.copied / this.database_progress.total * 100
        },
        totalDatabaseSize() {
            bytes = this.database.map(item => item.size).reduce((prev, next) => parseInt( prev ) + parseInt( next ) )
            return bytes
        },
        totalFileCount(){
            return this.files.map( file => file.count ).reduce((partialSum, a) => partialSum + a, 0)
        },
        migrateCommand() {
            if ( this.backup_token == '' || ! this.backup_ready ) {
                return ""
            }
            command = `curl -s https://disembark.host/generate-zip | bash -s -- --url="${this.site_url}" \\
--token="${this.token}" --backup-token="${this.backup_token}" --cleanup`
            return command
        }
    }
}).use(vuetify).mount('#app');
</script>