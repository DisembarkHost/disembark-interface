<div id="app" v-cloak>
<v-app style="background:transparent">
    <v-main>
    <v-container>
    <v-card style="max-width: 600px; margin: 0px auto 20px auto;" class="pa-3">
    <v-card-text>
    <v-row>
        <v-col cols="12" class="py-0">
          <v-text-field v-model="site_url" label="Site URL" hide-details spellcheck="false"></v-text-field>
        </v-col>
        <v-col cols="12" class="py-0">
            <v-row>
                <v-col cols="4"><v-checkbox v-model="use_token" readonly><template v-slot:label><div>Use token? 
                <v-tooltip bottom>
                <template v-slot:activator="{ on }">
                    <a href="https://github.com/DisembarkHost/disembark-connector/releases" target="_blank"><v-icon @click.stop v-on="on">mdi-information</v-icon></a>
                </template>
                <span>Download Disembark Connector required for token connection.</span>
                </v-tooltip>
                </div></template></v-checkbox></v-col>
                <v-col cols="8"><v-text-field label="Token" spellcheck="false" v-show="use_token" v-model="token" class="token"></v-text-field></v-col>
            </v-row>
        </v-col>
        <v-col cols="12" sm="6" md="6" class="py-0" v-show="! use_token">
          <v-text-field label="Username" spellcheck="false"></v-text-field>
        </v-col>
        <v-col cols="12" sm="6" md="6" class="py-0" v-show="! use_token">
          <v-text-field type="password" label="Password" spellcheck="false"></v-text-field>
        </v-col>
        <v-col cols="12">
            <v-btn block color="var(--theme-palette-color-4)" dark @click="connect()">Begin Backup Snapshot <v-icon class="ml-2">mdi-cloud-download</v-icon></v-btn>
        </v-col>
    </v-row>
    </v-card-text>
    </v-card>
    <v-alert outlined type="success" text v-if="backup_token != '' && files_progress.copied == files_progress.total">
      Backup is ready. You can generate a zip file locally use the following commands in your terminal.
        <pre style="font-size: 11px;color: var(--theme-palette-color-1);margin: 14px 14px 0px 0px;background: var(--theme-palette-color-2);">curl -s https://disembark.host/generate-zip | bash -s -- --url="{{ site_url }}" \
--token="{{ token }}" --backup-token="{{ backup_token }}"</pre>
    </v-alert>
    <v-row>
        <v-col cols="12" sm="12" md="6" v-if="database.length > 0">
        <v-toolbar flat dark dense color="var(--theme-palette-color-2)">
            <v-toolbar-title>Database</v-toolbar-title>
            <v-spacer></v-spacer>
            {{ totalDatabaseSize | formatSize }}
        </v-toolbar>
        <v-progress-linear v-model="databaseProgress" color="amber" height="25">
            Copied {{ database_progress.copied }} of {{ database.length }} tables
        </v-progress-linear>
        <v-simple-table dense>
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
                <td>{{ item.table }}</td>
                <td>{{ item.size | formatSize }}</td>
                </tr>
            </tbody>
            </template>
        </v-simple-table>
        </v-col>
        <v-col cols="12" sm="12" md="6" v-if="files.length > 0">
        <v-toolbar flat dark dense color="var(--theme-palette-color-2)">
            <v-toolbar-title>Files</v-toolbar-title>
            <v-spacer></v-spacer>
            {{ files_total | formatSize }}
        </v-toolbar>
        <v-progress-linear v-model="filesProgress" color="amber" height="25">
        Copied {{ files_progress.copied | formatLargeNumbers }} of {{ files_progress.total | formatLargeNumbers }}
        </v-progress-linear>
        </v-col>
    </v-row>
    </v-container>
    <v-snackbar :timeout="3000" :multi-line="true" v-model="snackbar.show" style="z-index: 9999999;" color="var(--theme-palette-color-2)">
        {{ snackbar.message }}
        <v-btn dark text @click.native="snackbar.show = false">Close</v-btn>
    </v-snackbar>
    </v-main>
</v-app>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.7.2/dist/vuetify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios@1.7.2/dist/axios.min.js"></script>
<script>
new Vue({
    el: '#app',
    vuetify: new Vuetify(),
    data: {
        site_url: "",
        token: "",
        backup_token: "",
		snackbar: { show: false, message: "" },
        use_token: true,
        database_progress: { copied: 0, total: 0 },
        files_progress: { copied: 0, total: 0 },
        backup_progress: { copied: 0, total: 0 },
		database: [],
        files: [],
        files_total: 0
    },
    methods: {
        backupFiles() {
            if ( this.files_progress.copied == this.files_progress.total ) {
                return
            }
            file = this.files[ this.backup_progress.copied ]
            axios.post( '/wp-json/disembark/v1/remote/zip-files', {
                site_url: this.site_url,
                token: this.token,
                backup_token: this.backup_token,
                file: file.name
            }).then( response => {
                if ( response.data == "" ) {
                    this.snackbar.message = `Could not zip ${file.name}.`
                    this.snackbar.show = true
                    return
                }
                this.files_progress.copied = this.files_progress.copied + file.count
                this.backup_progress.copied = this.backup_progress.copied + 1
                this.backupFiles()
            })
        },
        backupDatabase() {
            tables = this.database.map( item => item.table )
            if ( this.database_progress.copied == this.database_progress.total ) {
                this.zipDatabase()
                this.backupFiles()
                return
            }
            table = tables[ this.database_progress.copied ]
            axios.post( '/wp-json/disembark/v1/remote/export-database', {
                site_url: this.site_url,
                token: this.token,
                backup_token: this.backup_token,
                table: table
            }).then( response => {
                if ( response.data == "" ) {
                    this.snackbar.message = `Could not backup table ${table}.`
                    this.snackbar.show = true
                    return
                }
                this.database_progress.copied = this.database_progress.copied + 1
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
        connect() {
            this.snackbar.message = `Analyzing ${this.site_url}.`
			this.snackbar.show = true
            axios.post( '/wp-json/disembark/v1/remote/connect', {
				site_url: this.site_url,
                token: this.token
			})
			.then( response => {
				if ( response.data == "" ) {
					this.snackbar.message = `Could not connect to ${this.site_url}. Verify token or WordPress login.`
				    this.snackbar.show = true
					return
				}
                this.database = response.data.database
                this.database_progress.total = this.database.length
                this.backup_token = response.data.token
                this.files = response.data.files
                this.files_progress.total = response.data.files.map( file => file.count ).reduce((partialSum, a) => partialSum + a, 0);
                this.backup_progress.total = response.data.files.length
                this.files_total = response.data.files.map( file => file.size ).reduce((partialSum, a) => partialSum + a, 0);
                this.backupDatabase()
			});
        }
    },
    mounted() {
        this.$vuetify.theme.themes.light.primary = getComputedStyle(document.documentElement).getPropertyValue('--theme-palette-color-2');
    },
    filters: {
        formatLargeNumbers: function (number) {
			if ( isNaN(number) || number == null ) {
				return null;
			} else {
				return number.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
			}
		},
        formatSize: function (fileSizeInBytes) {
			var i = -1;
			var byteUnits = [' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
			do {
				fileSizeInBytes = fileSizeInBytes / 1024;
				i++;
			} while (fileSizeInBytes > 1024);
    		return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
		},
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
    }
})
</script>