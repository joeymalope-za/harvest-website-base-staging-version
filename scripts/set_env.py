# This script is a primitive environment manager, which does following:
# 1. substitute occurrences of ${VAR} in ./envs/$HARVEST_ENV|all/**/file with either value from corresponding .values
# file, or environment variable, or default value
# 2. replace files in ./public with files in ./envs/all/, and then ./envs/$HARVEST_ENV/
import argparse
import os
import shutil
import re


def scantree(path):
    """Recursively yield DirEntry objects for given directory."""
    for entry in os.scandir(path):
        if entry.is_dir(follow_symlinks=False):
            yield from scantree(entry.path)  # see below for Python 2.x
        else:
            yield entry


def get_template_matches(content):
    template_pattern = r'\$\{([^:\-\}]+)(?:\:\-([^}]*))?\}'
    parts = content.split('`')
    # odd indexes are inside ` - skip to not mess JS
    matches = [re.findall(template_pattern, parts[i]) for i in range(len(parts)) if i % 2 == 0]
    return [match for sublist in matches for match in sublist]


def replace_variables(file_path, values_file_paths, use_env=True):
    values = {}
    for values_file_path in values_file_paths:
        if os.path.isfile(values_file_path):
            with open(values_file_path, 'r') as values_file:
                for line in values_file:
                    key, value = line.strip().split('=', 1)
                    values[key] = value

    with open(file_path, 'r') as file:
        content = file.read()

    matches = get_template_matches(content)

    for variable, default_value in matches:
        value = None
        if variable in values:
            value = values[variable]
        elif use_env and variable in os.environ:
            value = os.environ[variable]
        elif default_value is not None:
            value = default_value
        else:
            print(f"Variable '{variable}' in {file_path} does not have a value")
            exit(-1)
        if default_value is not None:
            content = content.replace(f'${{{variable}:-{default_value}}}', value)
            content = content.replace(f'${{{variable}}}', value)

    with open(file_path, 'w') as file:
        file.write(content)


if __name__ == '__main__':
    if os.getcwd().endswith('scripts'):
        os.chdir('..')
    ap = argparse.ArgumentParser()
    ap.add_argument('--envs_dir', help="Environments dir", default='envs')
    ap.add_argument('--dst_dir', help="Destination dir", default='public')
    ap.add_argument('--ignore_dirs', help="Comma-separated list of ignored directories", default='vendor/')
    ap.add_argument('--symlink', action='store_true',
                    help='Create symlinks instead of copying files. Only if the file does not contain templates.')
    args = ap.parse_args()
    args.envs_dir = args.envs_dir.rstrip('/')
    args.dst_dir = args.dst_dir.rstrip('/')
    ignored_dirs = args.ignore_dirs.split(',')
    harvest_env = os.getenv('HARVEST_ENV')
    if not harvest_env:
        print("Error HARVEST_ENV not set")
        exit(-1)
    harvest_env = harvest_env.lower()
    if os.path.exists('.env'):
        with open('.env', 'r') as fh:
            vars_dict = dict(
                tuple(line.replace('\n', '').split('=', maxsplit=1))
                for line in fh.readlines() if not line.startswith('#') and line.strip()
            )
        os.environ.update(vars_dict)

    print(f"Configuring Harvest environment for {harvest_env}...")
    env_dir = f'{args.envs_dir}/{harvest_env}/'
    dst_dir = f'{args.dst_dir}/'
    all_dir = f'{args.envs_dir}/all/'
    if not os.path.exists(env_dir):
        print(f'Environment dir {env_dir} not found')
    print(f"Copying files...")


    def ignore_values_files(dirname, filenames):
        return [filename for filename in filenames if filename.endswith('.values')]


    all_files = [f for f in scantree(all_dir) if f.is_file() and not f.name.endswith('.values') and not any(i for i in ignored_dirs if i in f.path)]
    env_files = [f for f in scantree(env_dir) if f.is_file() and not f.name.endswith('.values') and not any(i for i in ignored_dirs if i in f.path)]
    src_dst_files = {}
    for f in env_files:
        src_dst_files[f.path] = f.path.replace(env_dir, dst_dir)
    for f in all_files:
        # does it have overriding file?
        if f.path.replace(all_dir, env_dir) not in env_files:
            src_dst_files[f.path] = f.path.replace(all_dir, dst_dir)
    for src, dst in src_dst_files.items():
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        copy = False
        if os.path.exists(dst):
            os.remove(dst)
        if args.symlink:
            with open(src, mode='r') as src_file:
                has_templates = len(get_template_matches(src_file.read())) > 0
            if not has_templates:
                os.symlink(os.path.abspath(src), os.path.abspath(dst))
            else:
                print(f'Cannot symlink file {src} because it uses templates')
                copy = True
        else:
            copy = True
        if copy:
            shutil.copy(src, dst)
        if src.startswith(all_dir):
            # if the file is in all dir - use values from env dir, if present
            replace_variables(dst, [src + '.values', src.replace(all_dir, env_dir) + '.values'])
        else:
            # if the file is in env dir - first use vals from env dir, and then from all dir
            replace_variables(dst, [src.replace(env_dir, all_dir) + '.values', src + '.values'])
