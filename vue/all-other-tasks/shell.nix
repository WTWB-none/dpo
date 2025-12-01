{
  pkgs ? import <nixpkgs> { },
}:
pkgs.mkShell {
  packages = [ pkgs.mongodb-ce ];
  shellHook = ''
    	mongod --noauth --quiet --bind_ip 127.0.0.1 --dbpath ./database > /dev/null  
  '';
}
