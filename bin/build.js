import * as esbuild from 'esbuild'
import { readdirSync } from 'node:fs'
import { basename } from 'node:path'

const isDev = process.argv.includes('--dev')
const componentsDir = './resources/js/components'

const entryPoints = readdirSync(componentsDir)
    .filter((file) => file.endsWith('.js'))
    .map((file) => ({
        in: `${componentsDir}/${file}`,
        out: basename(file, '.js'),
    }))

/** @type {import('esbuild').BuildOptions} */
const options = {
    entryPoints,
    outdir: './resources/dist',
    bundle: true,
    format: 'esm',
    platform: 'neutral',
    mainFields: ['module', 'main'],
    target: ['es2020'],
    minify: !isDev,
    sourcemap: isDev ? 'inline' : false,
    sourcesContent: isDev,
    treeShaking: true,
    logLevel: 'info',
    define: {
        'process.env.NODE_ENV': JSON.stringify(isDev ? 'development' : 'production'),
    },
    plugins: [
        {
            name: 'watch-log',
            setup(build) {
                build.onEnd((result) => {
                    if (isDev) console.log(`[${new Date().toLocaleTimeString()}] rebuilt, ${result.errors.length} error(s)`)
                })
            },
        },
    ],
}

if (isDev) {
    const context = await esbuild.context(options)
    await context.watch()
} else {
    await esbuild.build(options)
}
