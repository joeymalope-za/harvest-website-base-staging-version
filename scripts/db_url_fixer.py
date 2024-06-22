# This script replaces pre-defined list of URLs in WordPress database dump to supplement development-staging-production configuration management.
# The repository holds database dump with development URLs.
import argparse
import subprocess
from sys import platform
import os

if __name__ == '__main__':
    if os.getcwd().endswith('scripts'):
        os.chdir('..')

    ap = argparse.ArgumentParser()
    ap.add_argument('--reverse', help="Replace local urls with staging/production. Affects how it deals with https.",
                    action='store_true', default=False)
    ap.add_argument('--src_domains',
                    help="comma-separated list of source domains (without protocols, but can contain ports)",
                    required=True)
    ap.add_argument('--dst_domains',
                    help="comma-separated list of replacement domains (without protocols, but can contain ports)",
                    required=True)
    ap.add_argument('--dump_path', default='data/dump.sql')
    args = ap.parse_args()
    """
sed -i "" "s/https:\/\/$SITE_DOMAIN/http:\/\/$LOCAL_DOMAIN/g" data/$DB_FILE
sed -i "" "s/http:\/\/$SITE_DOMAIN/http:\/\/$LOCAL_DOMAIN/g" data/$DB_FILE
sed -i "" "s/$SITE_DOMAIN/$LOCAL_DOMAIN/g" data/$DB_FILE
"""
    # switch https to http for local mode, and switch back for staging and production
    src_domains = args.src_domains.split(',')
    dst_domains = args.dst_domains.split(',')
    if len(src_domains) != len(dst_domains):
        raise ValueError('Source and destination domain list do not match')

    prefix_map = {
        'https://': 'http://',
        # some db fields have slashes escaped
        # why it takes 7 and not 6 \ to produce \\/ for sed?
        r'https:\\\\\\\/\\\\\\\/': r'http:\\\\\\\/\\\\\\\/',
        '': '',
    }
    if args.reverse:
        prefix_map = {
            'http://': 'https://',
            r'http:\\\\\\\/\\\\\\\/': r'https:\\\\\\\/\\\\\\\/',
            '': '',
        }
    sed_extra_arg = '""' if platform == 'darwin' else ''
    for src, dst in zip(src_domains, dst_domains):
        for psrc, pdst in prefix_map.items():
            print(f'Replace {psrc}{src} with {pdst}{dst}')
            subprocess.check_output(f'sed -i {sed_extra_arg} "s;{psrc}{src};{pdst}{dst};g" {args.dump_path}', shell=True)
